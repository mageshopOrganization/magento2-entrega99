<?php
/**
 * MageShop | Entrega99
 *
 * @category MageShop
 * @package  Entrega99
 */

declare(strict_types=1);

namespace MageShop\Entrega99\Model;

use MageShop\Entrega99\Api\Data\OrderShipmentInterface;
use MageShop\Entrega99\Api\Data\OrderShipmentInterfaceFactory;
use MageShop\Entrega99\Api\Data\WaypointInterface;
use MageShop\Entrega99\Api\GeocoderInterface;
use MageShop\Entrega99\Api\OrderShipmentRepositoryInterface;
use MageShop\Entrega99\Api\WaypointRepositoryInterface;
use MageShop\Entrega99\Exception\ApiException;
use MageShop\Entrega99\Helper\Data as Helper;
use MageShop\Entrega99\Model\Api\Client;
use MageShop\Entrega99\Model\Carrier\Entrega99 as CarrierConsts;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;

/**
 * Creates a 99Entrega delivery for a Magento order.
 *
 * Flow:
 *  1. Load order + OrderShipment placeholder (created by SalesOrderPlaceBefore observer)
 *  2. Resolve waypoint (pickup origin)
 *  3. Re-quote the delivery (the estimate from checkout may have expired — 30min TTL)
 *  4. Call /v2/order/create with the fresh estimate_id
 *  5. Handle errno 6102 (duplicate external_order_id) by fetching the existing order
 *  6. Persist 99 order_id + tracking link + status on OrderShipment
 *  7. Add a comment to the Magento order with the tracking URL
 */
class CreateShipment
{
    private const ERRNO_DUPLICATE_EXTERNAL_ORDER_ID = 6102;
    private const ERRNO_ESTIMATE_EXPIRED            = 4002;

    /**
     * Maps statuses returned by 99 API (mostly snake_case but a few camelCase)
     * to our local OrderShipment status constants.
     */
    private const API_STATUS_MAP = [
        'finding'            => OrderShipmentInterface::STATUS_FINDING,
        'waiting'            => OrderShipmentInterface::STATUS_WAITING,
        'delivering'         => OrderShipmentInterface::STATUS_DELIVERING,
        'completed'          => OrderShipmentInterface::STATUS_COMPLETED,
        'canceled'           => OrderShipmentInterface::STATUS_CANCELED,
        'closed'             => OrderShipmentInterface::STATUS_CLOSED,
        'sendback'           => OrderShipmentInterface::STATUS_SENDBACK,
        'sendbackCompleted'  => OrderShipmentInterface::STATUS_SENDBACK_COMPLETED,
        'sendback_completed' => OrderShipmentInterface::STATUS_SENDBACK_COMPLETED,
    ];

    public function __construct(
        private readonly Helper $helper,
        private readonly Client $apiClient,
        private readonly GeocoderInterface $geocoder,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly OrderShipmentRepositoryInterface $orderShipmentRepository,
        private readonly OrderShipmentInterfaceFactory $orderShipmentFactory,
        private readonly WaypointRepositoryInterface $waypointRepository,
        private readonly PhoneFormatter $phoneFormatter,
        private readonly EventManager $eventManager
    ) {
    }

    /**
     * @throws LocalizedException
     */
    public function create(int $orderId): OrderShipmentInterface
    {
        $order = $this->orderRepository->get($orderId);
        $this->assertCarrierIsEntrega99($order);

        $shipment = $this->loadOrCreatePlaceholder($order);
        $this->assertCanCreate($shipment);

        $storeId = (int)$order->getStoreId();
        $waypoint = $this->resolveWaypoint($shipment, $storeId);
        if ($waypoint->getLatitude() === null || $waypoint->getLongitude() === null) {
            throw new LocalizedException(__('Pickup waypoint is missing latitude/longitude.'));
        }

        $dropoffCoords = $this->geocodeShippingAddress($order, $storeId);
        if ($dropoffCoords === null) {
            throw new LocalizedException(__('Could not geocode the delivery address — cannot create 99Entrega order.'));
        }

        $vehicleType = $this->helper->getVehicleType($storeId);
        $externalOrderId = (string)$order->getIncrementId();

        // Step 1: fresh estimate (the one cached from checkout may have expired)
        $estimate = $this->fetchFreshEstimate($vehicleType, $waypoint, $dropoffCoords, $order, $storeId);

        // Step 2: build and send /order/create
        $payload = $this->buildCreatePayload(
            $order,
            $shipment,
            $waypoint,
            $dropoffCoords,
            $vehicleType,
            $externalOrderId,
            (string)$estimate['id'],
            $storeId
        );

        try {
            $response = $this->apiClient->post('/v2/order/create', $payload, $storeId);
        } catch (ApiException $e) {
            if ($e->getErrno() === self::ERRNO_DUPLICATE_EXTERNAL_ORDER_ID) {
                $this->helper->logInfo('99Entrega returned 6102 (duplicate). Reconciling.', [
                    'external_order_id' => $externalOrderId,
                ]);
                return $this->reconcileExisting($order, $shipment, $externalOrderId, $storeId);
            }
            throw $e;
        }

        $entrega99OrderId = (string)($response['order_id'] ?? '');
        if ($entrega99OrderId === '') {
            throw new LocalizedException(__('99Entrega /order/create response did not include order_id.'));
        }

        $shipment
            ->setEntrega99OrderId($entrega99OrderId)
            ->setExternalOrderId($externalOrderId)
            ->setEstimateId((string)$estimate['id'])
            ->setWaypointId((int)$waypoint->getWaypointId())
            ->setVehicleType($vehicleType)
            ->setFeeCents(isset($estimate['fee']) ? (int)$estimate['fee'] : null)
            ->setCurrency((string)($estimate['currency'] ?? 'R$'))
            ->setStatus(OrderShipmentInterface::STATUS_CREATED);

        // Optional fields the API may return at create time
        if (!empty($response['tracking_link'])) {
            $shipment->setTrackingLink((string)$response['tracking_link']);
        }
        if (!empty($response['pickup_code'])) {
            $shipment->setPickupCode((string)$response['pickup_code']);
        }
        if (!empty($response['dropoff_code'])) {
            $shipment->setDropoffCode((string)$response['dropoff_code']);
        }

        $this->orderShipmentRepository->save($shipment);
        $this->addOrderComment($order, $entrega99OrderId, $shipment->getTrackingLink());

        $this->eventManager->dispatch('mageshop_entrega99_shipment_create_after', [
            'order'    => $order,
            'shipment' => $shipment,
            'response' => $response,
        ]);

        $this->helper->logInfo('99Entrega delivery created', [
            'order'        => $externalOrderId,
            'entrega99_id' => $entrega99OrderId,
        ]);

        return $shipment;
    }

    // ============== Helpers ==============

    private function assertCarrierIsEntrega99(Order $order): void
    {
        $expected = CarrierConsts::CARRIER_CODE . '_' . CarrierConsts::METHOD_CODE;
        if ($order->getShippingMethod() !== $expected) {
            throw new LocalizedException(__('Order %1 does not use the 99Entrega shipping method.', $order->getIncrementId()));
        }
    }

    private function assertCanCreate(OrderShipmentInterface $shipment): void
    {
        // STATUS_CANCELED is intentionally NOT here — recreating a canceled
        // delivery would silently resurrect it (the carrier sees the same
        // external_order_id and we end up reconciling against the canceled row).
        $allowedToRetry = [
            OrderShipmentInterface::STATUS_PENDING,
            OrderShipmentInterface::STATUS_FAILED,
        ];
        if (!in_array($shipment->getStatus(), $allowedToRetry, true)) {
            throw new LocalizedException(
                __('99Entrega delivery cannot be created from status: %1.', $shipment->getStatus())
            );
        }
    }

    private function loadOrCreatePlaceholder(Order $order): OrderShipmentInterface
    {
        try {
            return $this->orderShipmentRepository->getByOrderId((int)$order->getId());
        } catch (NoSuchEntityException) {
            $shipment = $this->orderShipmentFactory->create();
            $shipment->setOrderId((int)$order->getId())
                ->setStoreId($order->getStoreId() !== null ? (int)$order->getStoreId() : null)
                ->setIncrementId((string)$order->getIncrementId())
                ->setExternalOrderId((string)$order->getIncrementId())
                ->setStatus(OrderShipmentInterface::STATUS_PENDING);
            return $shipment;
        }
    }

    private function resolveWaypoint(OrderShipmentInterface $shipment, int $storeId): WaypointInterface
    {
        $waypointId = $shipment->getWaypointId();
        if ($waypointId !== null && $waypointId > 0) {
            try {
                return $this->waypointRepository->getById($waypointId);
            } catch (NoSuchEntityException) {
                $this->helper->logInfo('Stored waypoint not found, falling back to first active', ['waypoint_id' => $waypointId]);
            }
        }
        $waypoint = $this->waypointRepository->getFirstActive($storeId);
        if ($waypoint === null) {
            throw new LocalizedException(__('No active 99Entrega waypoint configured.'));
        }
        return $waypoint;
    }

    /**
     * @return array{latitude: float, longitude: float}|null
     */
    private function geocodeShippingAddress(Order $order, int $storeId): ?array
    {
        $address = $order->getShippingAddress();
        if ($address === null) {
            return null;
        }

        $regionName = (string)$address->getRegion();
        $streetLines = $address->getStreet();
        $street = $streetLines[0] ?? '';
        $number = $streetLines[1] ?? '';
        $neighborhood = $streetLines[3] ?? ($streetLines[2] ?? '');

        $parts = [
            'street'       => (string)$street,
            'number'       => (string)$number,
            'neighborhood' => (string)$neighborhood,
            'city'         => (string)$address->getCity(),
            'region'       => $regionName,
            'postcode'     => (string)$address->getPostcode(),
            'country'      => (string)($address->getCountryId() ?: 'BR'),
        ];

        $result = $this->geocoder->geocode($parts, $storeId);
        if ($result === null) {
            return null;
        }
        return ['latitude' => $result->latitude, 'longitude' => $result->longitude];
    }

    /**
     * @param array{latitude: float, longitude: float} $dropoffCoords
     * @return array  the /estimate response data
     */
    private function fetchFreshEstimate(
        string $vehicleType,
        WaypointInterface $waypoint,
        array $dropoffCoords,
        Order $order,
        int $storeId
    ): array {
        $estimate = $this->apiClient->post('/v2/order/estimate', [
            'vehicle_type' => $vehicleType,
            'pickup_info'  => [
                'location'           => [
                    'lat' => (float)$waypoint->getLatitude(),
                    'lng' => (float)$waypoint->getLongitude(),
                ],
                'structured_address' => $this->buildWaypointStructuredAddress($waypoint),
            ],
            'dropoff_info' => [
                'location'           => [
                    'lat' => $dropoffCoords['latitude'],
                    'lng' => $dropoffCoords['longitude'],
                ],
                'structured_address' => $this->buildOrderStructuredAddress($order),
            ],
        ], $storeId);

        if (empty($estimate['id'])) {
            throw new LocalizedException(__('99Entrega /v2/order/estimate did not return an id.'));
        }
        return $estimate;
    }

    /**
     * @param array{latitude: float, longitude: float} $dropoffCoords
     */
    private function buildCreatePayload(
        Order $order,
        OrderShipmentInterface $shipment,
        WaypointInterface $waypoint,
        array $dropoffCoords,
        string $vehicleType,
        string $externalOrderId,
        string $estimateId,
        int $storeId
    ): array {
        $shippingAddress = $order->getShippingAddress();
        $customerName = $shippingAddress?->getName() ?: $order->getCustomerName();
        $customerPhone = $this->phoneFormatter->format(
            $shippingAddress?->getTelephone(),
            (string)($shippingAddress?->getCountryId() ?: 'BR')
        );

        $pickupPhone = $this->phoneFormatter->format(
            $waypoint->getTelephone(),
            $waypoint->getCountry()
        );

        return [
            'vehicle_type'            => $vehicleType,
            'external_order_id'       => $externalOrderId,
            'estimate_id'             => $estimateId,
            'need_pickup_code'        => $this->helper->needPickupCode($storeId),
            'need_dropoff_code'       => $this->helper->needDropoffCode($storeId),
            'return_handover_method'  => $this->helper->getReturnHandoverMethod($storeId),
            'pickup_info' => [
                'name'               => (string)($waypoint->getContactName() ?: $waypoint->getName()),
                'phone'              => $pickupPhone ?: '',
                'note'               => (string)($waypoint->getInstructions() ?: ''),
                'location'           => [
                    'lat' => (float)$waypoint->getLatitude(),
                    'lng' => (float)$waypoint->getLongitude(),
                ],
                'structured_address' => $this->buildWaypointStructuredAddress($waypoint),
            ],
            'dropoff_info' => [
                'name'               => (string)$customerName,
                'phone'              => $customerPhone ?: '',
                'note'               => $order->getCustomerNote() !== null ? (string)$order->getCustomerNote() : '',
                'location'           => [
                    'lat' => $dropoffCoords['latitude'],
                    'lng' => $dropoffCoords['longitude'],
                ],
                'structured_address' => $this->buildOrderStructuredAddress($order),
            ],
            'package_info' => [
                // 99 API accepts: groceries, food, documents, apparel, medication, electronics, others
                'package_type'   => 'others',
                // 99 API expects enum string with unit: "1kg", "5kg", "10kg", "20kg", "30kg"
                'package_weight' => $this->bucketWeightToApiValue(
                    $this->calculateTotalWeightGrams($order)
                ),
            ],
        ];
    }

    /**
     * Buckets the cart weight (in grams) to one of the values accepted by 99
     * for package_info.package_weight. The 99 API uses size buckets, not
     * actual weight.
     */
    private function bucketWeightToApiValue(float $grams): string
    {
        $kg = $grams / 1000.0;
        return match (true) {
            $kg <= 1   => '1kg',
            $kg <= 5   => '5kg',
            $kg <= 10  => '10kg',
            $kg <= 20  => '20kg',
            default    => '30kg',
        };
    }

    /**
     * @return array{street:string,number:string,complement:string,neighborhood:string,city:string,state:string,CEP:string,country:string}
     */
    private function buildWaypointStructuredAddress(WaypointInterface $waypoint): array
    {
        return [
            'street'       => (string)$waypoint->getAddress(),
            'number'       => (string)($waypoint->getNumber() ?? ''),
            'complement'   => (string)($waypoint->getComplement() ?? ''),
            'neighborhood' => (string)($waypoint->getNeighborhood() ?? ''),
            'city'         => (string)$waypoint->getCity(),
            'state'        => (string)($waypoint->getRegion() ?? ''),
            'CEP'          => preg_replace('/\D+/', '', (string)$waypoint->getPostcode()) ?: '',
            'country'      => (string)$waypoint->getCountry(),
        ];
    }

    /**
     * @return array{street:string,number:string,complement:string,neighborhood:string,city:string,state:string,CEP:string,country:string}
     */
    private function buildOrderStructuredAddress(Order $order): array
    {
        $address = $order->getShippingAddress();
        if ($address === null) {
            return [
                'street'       => '',
                'number'       => '',
                'complement'   => '',
                'neighborhood' => '',
                'city'         => '',
                'state'        => '',
                'CEP'          => '',
                'country'      => 'BR',
            ];
        }
        $streetLines = $address->getStreet() ?: [];
        return [
            'street'       => (string)($streetLines[0] ?? ''),
            'number'       => (string)($streetLines[1] ?? ''),
            'complement'   => (string)($streetLines[2] ?? ''),
            'neighborhood' => (string)($streetLines[3] ?? ''),
            'city'         => (string)($address->getCity() ?? ''),
            'state'        => (string)($address->getRegion() ?? ''),
            'CEP'          => preg_replace('/\D+/', '', (string)($address->getPostcode() ?? '')) ?: '',
            'country'      => (string)($address->getCountryId() ?: 'BR'),
        ];
    }

    private function calculateTotalWeightGrams(Order $order): float
    {
        $totalNative = 0.0;
        foreach ($order->getAllItems() as $item) {
            if ($item->getParentItem()) {
                continue; // skip child of configurable
            }
            $totalNative += (float)$item->getWeight() * (float)$item->getQtyOrdered();
        }
        // 99Entrega expects grams. Honor the store's weight unit config.
        $unit = $this->helper->getStoreWeightUnit((int)$order->getStoreId());
        return $unit === 'lbs' ? $totalNative * 453.592 : $totalNative * 1000.0;
    }

    private function formatWaypointAddress(WaypointInterface $waypoint): string
    {
        $parts = array_filter([
            trim($waypoint->getAddress()),
            $waypoint->getNumber() !== null ? trim((string)$waypoint->getNumber()) : null,
            $waypoint->getNeighborhood() !== null ? trim((string)$waypoint->getNeighborhood()) : null,
            trim($waypoint->getCity()),
            $waypoint->getRegion() !== null ? trim((string)$waypoint->getRegion()) : null,
            trim($waypoint->getPostcode()),
            $waypoint->getCountry(),
        ]);
        return implode(', ', $parts);
    }

    private function formatOrderShippingAddress(Order $order): string
    {
        $address = $order->getShippingAddress();
        if ($address === null) {
            return '';
        }
        $streetLines = $address->getStreet() ?: [];
        $parts = array_filter([
            !empty($streetLines[0]) ? trim((string)$streetLines[0]) : null,
            !empty($streetLines[1]) ? trim((string)$streetLines[1]) : null,
            !empty($streetLines[2]) ? trim((string)$streetLines[2]) : null,
            !empty($streetLines[3]) ? trim((string)$streetLines[3]) : null,
            $address->getCity() ? trim((string)$address->getCity()) : null,
            $address->getRegion() ? trim((string)$address->getRegion()) : null,
            $address->getPostcode() ? trim((string)$address->getPostcode()) : null,
            $address->getCountryId() ? (string)$address->getCountryId() : null,
        ]);
        return implode(', ', $parts);
    }

    /**
     * Recovers from errno 6102 by GET /v2/order/detail with the external_order_id.
     */
    private function reconcileExisting(
        Order $order,
        OrderShipmentInterface $shipment,
        string $externalOrderId,
        int $storeId
    ): OrderShipmentInterface {
        $detail = $this->apiClient->get('/v2/order/detail', ['external_order_id' => $externalOrderId], $storeId);
        $entrega99OrderId = (string)($detail['order_id'] ?? '');
        if ($entrega99OrderId === '') {
            throw new LocalizedException(__('Duplicate external_order_id but /order/detail did not return order_id.'));
        }

        $apiStatus = (string)($detail['status'] ?? '');
        $localStatus = self::API_STATUS_MAP[$apiStatus] ?? OrderShipmentInterface::STATUS_CREATED;

        $shipment->setEntrega99OrderId($entrega99OrderId)
            ->setExternalOrderId($externalOrderId)
            ->setStatus($localStatus);
        if (!empty($detail['tracking_link'])) {
            $shipment->setTrackingLink((string)$detail['tracking_link']);
        }
        $this->orderShipmentRepository->save($shipment);

        $this->addOrderComment($order, $entrega99OrderId, $shipment->getTrackingLink(), true);
        return $shipment;
    }

    private function addOrderComment(Order $order, string $entrega99OrderId, ?string $trackingLink, bool $reconciled = false): void
    {
        $lines = [];
        $lines[] = $reconciled
            ? (string)__('99Entrega delivery reconciled (already existed at 99): <b>%1</b>', $entrega99OrderId)
            : (string)__('99Entrega delivery created: <b>%1</b>', $entrega99OrderId);
        if ($trackingLink !== null && $trackingLink !== '') {
            $lines[] = (string)__('Tracking: <a href="%1" target="_blank">%1</a>', $trackingLink);
        }
        try {
            $history = $order->addCommentToStatusHistory(implode('<br>', $lines));
            $history->setIsCustomerNotified(false);
            $this->orderRepository->save($order);
        } catch (\Throwable $e) {
            $this->helper->logException($e, 'Failed to add order comment');
        }
    }
}
