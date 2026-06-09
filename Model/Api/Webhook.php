<?php
/**
 * MageShop | Entrega99
 *
 * @category MageShop
 * @package  Entrega99
 */

declare(strict_types=1);

namespace MageShop\Entrega99\Model\Api;

use MageShop\Entrega99\Api\Data\OrderShipmentInterface;
use MageShop\Entrega99\Api\Data\WebhookEventInterface;
use MageShop\Entrega99\Api\Data\WebhookEventInterfaceFactory;
use MageShop\Entrega99\Api\OrderShipmentRepositoryInterface;
use MageShop\Entrega99\Api\WebhookEventRepositoryInterface;
use MageShop\Entrega99\Api\WebhookInterface;
use MageShop\Entrega99\Helper\Data as Helper;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Exception\AuthorizationException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Webapi\Rest\Request as RestRequest;
use Magento\Sales\Api\OrderRepositoryInterface;

class Webhook implements WebhookInterface
{
    private const SIGNATURE_HEADER = 'X-Webhook-Signature';

    /**
     * Maps 99Entrega webhook event names to local OrderShipment statuses.
     */
    private const EVENT_STATUS_MAP = [
        'DriverAccepted'    => OrderShipmentInterface::STATUS_DRIVER_ACCEPTED,
        'DriverArrived'     => OrderShipmentInterface::STATUS_DRIVER_ARRIVED,
        'DriverBeginCharge' => OrderShipmentInterface::STATUS_DELIVERING,
        'DriverCanceled'    => OrderShipmentInterface::STATUS_CANCELED,
        'BroadcastTimeout'  => OrderShipmentInterface::STATUS_FAILED,
        'OrderCompleted'    => OrderShipmentInterface::STATUS_COMPLETED,
        'OrderClosed'       => OrderShipmentInterface::STATUS_CLOSED,
        'SendBack'          => OrderShipmentInterface::STATUS_SENDBACK,
        'SendBackCompleted' => OrderShipmentInterface::STATUS_SENDBACK_COMPLETED,
    ];

    public function __construct(
        private readonly Helper $helper,
        private readonly RestRequest $request,
        private readonly WebhookEventRepositoryInterface $webhookEventRepository,
        private readonly WebhookEventInterfaceFactory $webhookEventFactory,
        private readonly OrderShipmentRepositoryInterface $orderShipmentRepository,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly EventManager $eventManager
    ) {
    }

    public function receive(string $event, string $event_id, string $message, int $timestamp): array
    {
        $this->helper->logInfo('99Entrega webhook received', [
            'event'    => $event,
            'event_id' => $event_id,
            'timestamp'=> $timestamp,
        ]);

        $rawBody = (string)$this->request->getContent();

        // 1) HMAC signature
        $this->verifySignature($rawBody);

        // 2) Idempotency
        $existing = $this->webhookEventRepository->findByEventId($event_id);
        if ($existing !== null) {
            $this->helper->logInfo('99Entrega webhook duplicate ignored', ['event_id' => $event_id]);
            return ['status' => 'duplicate', 'event_id' => $event_id];
        }

        // 3) Persist record (before processing, for forensics)
        /** @var WebhookEventInterface $eventEntity */
        $eventEntity = $this->webhookEventFactory->create();
        $eventEntity->setEventId($event_id)
            ->setEventType($event)
            ->setEventTimestamp($timestamp)
            ->setPayload($message)
            ->setProcessed(false);
        $this->webhookEventRepository->save($eventEntity);

        // 4) Parse inner message and find related order
        $messageData = $this->decodeMessage($message);
        $entrega99OrderId = isset($messageData['order_id']) ? (string)$messageData['order_id'] : null;
        $externalOrderId  = isset($messageData['external_order_id']) ? (string)$messageData['external_order_id'] : null;

        if ($entrega99OrderId !== null) {
            $eventEntity->setEntrega99OrderId($entrega99OrderId);
        }
        if ($externalOrderId !== null) {
            $eventEntity->setExternalOrderId($externalOrderId);
        }

        $shipment = $this->findShipment($entrega99OrderId, $externalOrderId);

        if ($shipment === null) {
            $this->markProcessed($eventEntity, 'No matching OrderShipment');
            $this->helper->logInfo('99Entrega webhook received but no local shipment matches', [
                'event'             => $event,
                'event_id'          => $event_id,
                'entrega99_order_id'=> $entrega99OrderId,
                'external_order_id' => $externalOrderId,
            ]);
            return ['status' => 'ignored', 'reason' => 'no_match', 'event_id' => $event_id];
        }

        // 5) Apply status update + dispatch event for extension hooks
        $this->applyEvent($event, $messageData, $shipment);
        $this->markProcessed($eventEntity, 'ok');

        $this->eventManager->dispatch('mageshop_entrega99_webhook_received', [
            'event'    => $event,
            'event_id' => $event_id,
            'message'  => $messageData,
            'shipment' => $shipment,
        ]);

        return ['status' => 'ok', 'event_id' => $event_id];
    }

    // ============== Internals ==============

    /**
     * @throws AuthorizationException
     */
    private function verifySignature(string $rawBody): void
    {
        $signingKey = $this->helper->getWebhookSigningKey();
        if ($signingKey === '') {
            throw new AuthorizationException(
                __('99Entrega webhook signing key is not configured.')
            );
        }

        $received = (string)$this->request->getHeader(self::SIGNATURE_HEADER);
        if ($received === '') {
            $this->helper->logError('Webhook missing signature header');
            throw new AuthorizationException(__('Missing %1 header.', self::SIGNATURE_HEADER));
        }

        // Defensive: accept both raw hex and "sha256=<hex>" formats.
        $received = strtolower(trim((string)preg_replace('/^sha256=/i', '', trim($received))));

        $computed = hash_hmac('sha256', $rawBody, $signingKey);

        if (!hash_equals($computed, $received)) {
            $this->helper->logError('Webhook signature mismatch', [
                'received_prefix' => substr($received, 0, 12),
                'computed_prefix' => substr($computed, 0, 12),
            ]);
            throw new AuthorizationException(__('Invalid webhook signature.'));
        }
    }

    private function decodeMessage(string $message): array
    {
        $decoded = json_decode($message, true);
        if (!is_array($decoded)) {
            $this->helper->logError('Webhook message field is not valid JSON');
            return [];
        }
        return $decoded;
    }

    private function findShipment(?string $entrega99OrderId, ?string $externalOrderId): ?OrderShipmentInterface
    {
        if ($entrega99OrderId !== null && $entrega99OrderId !== '') {
            $shipment = $this->orderShipmentRepository->findByEntrega99OrderId($entrega99OrderId);
            if ($shipment !== null) {
                return $shipment;
            }
        }
        if ($externalOrderId !== null && $externalOrderId !== '') {
            return $this->orderShipmentRepository->findByExternalOrderId($externalOrderId);
        }
        return null;
    }

    private function applyEvent(string $event, array $message, OrderShipmentInterface $shipment): void
    {
        $newStatus = self::EVENT_STATUS_MAP[$event] ?? $shipment->getStatus();

        // Out-of-order protection: don't move backwards from terminal states
        if ($this->isTerminal($shipment->getStatus()) && !$this->isTerminal($newStatus)) {
            $this->helper->logInfo('99Entrega webhook ignored (older event vs terminal status)', [
                'current' => $shipment->getStatus(),
                'event'   => $event,
            ]);
            return;
        }

        $this->helper->logInfo('99Entrega webhook processed', [
            'event'             => $event,
            'previous_status'   => $shipment->getStatus(),
            'new_status'        => $newStatus,
            'order'             => $shipment->getIncrementId(),
            'entrega99_order_id'=> $shipment->getEntrega99OrderId(),
        ]);

        $shipment->setStatus($newStatus);

        if (isset($message['driver']) && is_array($message['driver'])) {
            $shipment->setDriverInfo(json_encode($message['driver'], JSON_UNESCAPED_UNICODE) ?: null);
        }
        if (!empty($message['tracking_link'])) {
            $shipment->setTrackingLink((string)$message['tracking_link']);
        }
        if (!empty($message['pickup_code'])) {
            $shipment->setPickupCode((string)$message['pickup_code']);
        }
        if (!empty($message['dropoff_code'])) {
            $shipment->setDropoffCode((string)$message['dropoff_code']);
        }

        $this->orderShipmentRepository->save($shipment);

        // Add to Magento order status history (does NOT change Magento order state — that's the merchant's call)
        try {
            $order = $this->orderRepository->get($shipment->getOrderId());
            $history = $order->addCommentToStatusHistory(
                (string)__('99Entrega: <b>%1</b> (status: %2)', $event, $newStatus)
            );
            $history->setIsCustomerNotified(false);
            $this->orderRepository->save($order);
        } catch (\Throwable $e) {
            $this->helper->logException($e, 'Failed to add webhook comment to order');
        }
    }

    private function isTerminal(string $status): bool
    {
        return in_array($status, [
            OrderShipmentInterface::STATUS_COMPLETED,
            OrderShipmentInterface::STATUS_CLOSED,
            OrderShipmentInterface::STATUS_SENDBACK_COMPLETED,
        ], true);
    }

    private function markProcessed(WebhookEventInterface $eventEntity, string $message): void
    {
        $eventEntity->setProcessed(true)
            ->setProcessMessage($message)
            ->setProcessedAt(gmdate('Y-m-d H:i:s'));
        try {
            $this->webhookEventRepository->save($eventEntity);
        } catch (\Throwable $e) {
            $this->helper->logException($e, 'Failed to mark webhook event processed');
        }
    }
}
