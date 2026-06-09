<?php
/**
 * MageShop | Entrega99
 *
 * @category MageShop
 * @package  Entrega99
 */

declare(strict_types=1);

namespace MageShop\Entrega99\Observer;

use MageShop\Entrega99\Api\Data\OrderShipmentInterface;
use MageShop\Entrega99\Api\Data\OrderShipmentInterfaceFactory;
use MageShop\Entrega99\Api\OrderShipmentRepositoryInterface;
use MageShop\Entrega99\Api\WaypointRepositoryInterface;
use MageShop\Entrega99\Helper\Data as Helper;
use MageShop\Entrega99\Model\CancelShipment;
use MageShop\Entrega99\Model\Carrier\Entrega99 as CarrierConsts;
use MageShop\Entrega99\Model\CreateShipment;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\Order;

/**
 * Two responsibilities:
 *  - Create the OrderShipment placeholder on the first save of an Entrega99 order.
 *    Resolves waypoint either from session data (set by SalesOrderPlaceBefore) or
 *    falls back to the first active waypoint for the store (for PWA / headless flows).
 *    Optionally triggers CreateShipment::create() when automatic_shipment is on.
 *
 *  - When an order transitions to canceled state, syncs the cancellation to 99Entrega
 *    (only if the delivery wasn't already in a terminal state at 99).
 */
class SalesOrderSaveAfter implements ObserverInterface
{
    private const SHIPPING_METHOD = CarrierConsts::CARRIER_CODE . '_' . CarrierConsts::METHOD_CODE;

    public function __construct(
        private readonly Helper $helper,
        private readonly OrderShipmentRepositoryInterface $orderShipmentRepository,
        private readonly OrderShipmentInterfaceFactory $orderShipmentFactory,
        private readonly WaypointRepositoryInterface $waypointRepository,
        private readonly CreateShipment $createShipment,
        private readonly CancelShipment $cancelShipment
    ) {
    }

    public function execute(Observer $observer): void
    {
        /** @var Order $order */
        $order = $observer->getEvent()->getOrder();
        if (!$order || !$order->getId()) {
            return;
        }

        if ($order->getShippingMethod() === self::SHIPPING_METHOD) {
            $this->ensurePlaceholder($order);
            $this->maybeAutoCreate($order);
        }

        $this->syncCancellationIfNeeded($order);
    }

    private function ensurePlaceholder(Order $order): void
    {
        $orderId = (int)$order->getId();
        try {
            $this->orderShipmentRepository->getByOrderId($orderId);
            return; // already exists
        } catch (NoSuchEntityException) {
            // create below
        }

        try {
            $waypointId = $order->getData('entrega99_waypoint_id');
            $waypointId = $waypointId !== null ? (int)$waypointId : null;
            if ($waypointId === null || $waypointId <= 0) {
                $fallback = $this->waypointRepository->getFirstActive((int)$order->getStoreId());
                $waypointId = $fallback?->getWaypointId();
            }

            $shipment = $this->orderShipmentFactory->create();
            $shipment->setOrderId($orderId)
                ->setStoreId($order->getStoreId() !== null ? (int)$order->getStoreId() : null)
                ->setIncrementId((string)$order->getIncrementId())
                ->setExternalOrderId((string)$order->getIncrementId())
                ->setWaypointId($waypointId)
                ->setVehicleType($order->getData('entrega99_vehicle_type'))
                ->setEstimateId($order->getData('entrega99_estimate_id'))
                ->setFeeCents($order->getData('entrega99_fee_cents') !== null ? (int)$order->getData('entrega99_fee_cents') : null)
                ->setCurrency($order->getData('entrega99_currency'))
                ->setStatus(OrderShipmentInterface::STATUS_PENDING);

            $this->orderShipmentRepository->save($shipment);

            $this->helper->logInfo('99Entrega placeholder created', [
                'order' => $order->getIncrementId(),
                'waypoint_id' => $waypointId,
            ]);
        } catch (\Throwable $e) {
            $this->helper->logException($e, 'Failed to create OrderShipment placeholder');
        }
    }

    private function maybeAutoCreate(Order $order): void
    {
        if (!$this->helper->isAutomaticShipment((int)$order->getStoreId())) {
            return;
        }

        try {
            $shipment = $this->orderShipmentRepository->getByOrderId((int)$order->getId());
            if ($shipment->getStatus() !== OrderShipmentInterface::STATUS_PENDING) {
                return; // already past the pending stage
            }
            $this->createShipment->create((int)$order->getId());
        } catch (\Throwable $e) {
            $this->helper->logException($e, 'Automatic CreateShipment failed for order ' . $order->getIncrementId());
            // mark as failed so admin sees it and can retry manually
            try {
                $shipment = $this->orderShipmentRepository->getByOrderId((int)$order->getId());
                $shipment->setStatus(OrderShipmentInterface::STATUS_FAILED);
                $this->orderShipmentRepository->save($shipment);
            } catch (\Throwable) {
                // ignore — already logged
            }
        }
    }

    private function syncCancellationIfNeeded(Order $order): void
    {
        if ($order->getState() !== Order::STATE_CANCELED) {
            return;
        }
        $origState = (string)$order->getOrigData('state');
        if ($origState === Order::STATE_CANCELED) {
            return; // already was canceled — not a fresh transition
        }

        try {
            $shipment = $this->orderShipmentRepository->getByOrderId((int)$order->getId());
        } catch (NoSuchEntityException) {
            return; // not an Entrega99 order
        }

        $terminalStatuses = [
            OrderShipmentInterface::STATUS_CANCELED,
            OrderShipmentInterface::STATUS_COMPLETED,
            OrderShipmentInterface::STATUS_CLOSED,
        ];
        if (in_array($shipment->getStatus(), $terminalStatuses, true)) {
            return;
        }

        try {
            $this->cancelShipment->cancel((int)$order->getId());
            $this->helper->logInfo('99Entrega delivery auto-canceled by Magento cancel', [
                'order' => $order->getIncrementId(),
            ]);
        } catch (\Throwable $e) {
            $this->helper->logException($e, 'Auto-cancel 99Entrega failed for order ' . $order->getIncrementId());
        }
    }
}
