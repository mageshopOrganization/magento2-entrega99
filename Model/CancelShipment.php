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
use MageShop\Entrega99\Api\OrderShipmentRepositoryInterface;
use MageShop\Entrega99\Exception\ApiException;
use MageShop\Entrega99\Helper\Data as Helper;
use MageShop\Entrega99\Model\Api\Client;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * Cancels a 99Entrega delivery via POST /v2/order/cancel.
 *
 * Default reason_id: 410013 (taken from the doc examples). The caller may
 * override (e.g. the admin controller may ask the user for a reason).
 */
class CancelShipment
{
    public const DEFAULT_REASON_ID = '410013';

    private const TERMINAL_STATUSES = [
        OrderShipmentInterface::STATUS_CANCELED,
        OrderShipmentInterface::STATUS_COMPLETED,
        OrderShipmentInterface::STATUS_CLOSED,
    ];

    public function __construct(
        private readonly Helper $helper,
        private readonly Client $apiClient,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly OrderShipmentRepositoryInterface $orderShipmentRepository,
        private readonly EventManager $eventManager
    ) {
    }

    /**
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function cancel(int $orderId, string $reasonId = self::DEFAULT_REASON_ID): OrderShipmentInterface
    {
        $shipment = $this->orderShipmentRepository->getByOrderId($orderId);

        if (in_array($shipment->getStatus(), self::TERMINAL_STATUSES, true)) {
            throw new LocalizedException(
                __('99Entrega delivery cannot be canceled (status: %1).', $shipment->getStatus())
            );
        }

        $entrega99OrderId = $shipment->getEntrega99OrderId();
        if ($entrega99OrderId === null || $entrega99OrderId === '') {
            // Never sent to 99 → just mark locally
            $shipment->setStatus(OrderShipmentInterface::STATUS_CANCELED);
            $this->orderShipmentRepository->save($shipment);
            $this->dispatchCancelEvent($orderId, $shipment, null);
            return $shipment;
        }

        $order = $this->orderRepository->get($orderId);
        $storeId = (int)$order->getStoreId();

        try {
            $response = $this->apiClient->post('/v2/order/cancel', [
                'order_id'  => $entrega99OrderId,
                'reason_id' => $reasonId,
            ], $storeId);
        } catch (ApiException $e) {
            $this->helper->logException($e, 'cancel failed at 99Entrega');
            throw $e;
        }

        $shipment->setStatus(OrderShipmentInterface::STATUS_CANCELED);
        $this->orderShipmentRepository->save($shipment);

        try {
            $history = $order->addCommentToStatusHistory(
                (string)__('99Entrega delivery canceled (reason: %1).', $reasonId)
            );
            $history->setIsCustomerNotified(false);
            $this->orderRepository->save($order);
        } catch (\Throwable $e) {
            $this->helper->logException($e, 'Failed to add cancel comment');
        }

        $this->dispatchCancelEvent($orderId, $shipment, $response);

        $this->helper->logInfo('99Entrega delivery canceled', [
            'order'        => $shipment->getIncrementId(),
            'entrega99_id' => $entrega99OrderId,
            'reason'       => $reasonId,
        ]);

        return $shipment;
    }

    private function dispatchCancelEvent(int $orderId, OrderShipmentInterface $shipment, ?array $response): void
    {
        $this->eventManager->dispatch('mageshop_entrega99_shipment_cancel_after', [
            'order_id' => $orderId,
            'shipment' => $shipment,
            'response' => $response,
        ]);
    }
}
