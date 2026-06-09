<?php
/**
 * MageShop | Entrega99
 *
 * @category MageShop
 * @package  Entrega99
 */

declare(strict_types=1);

namespace MageShop\Entrega99\Api\Data;

interface WebhookEventInterface
{
    public const ENTITY_ID          = 'entity_id';
    public const EVENT_ID           = 'event_id';
    public const EVENT_TYPE         = 'event_type';
    public const ENTREGA99_ORDER_ID = 'entrega99_order_id';
    public const EXTERNAL_ORDER_ID  = 'external_order_id';
    public const EVENT_TIMESTAMP    = 'event_timestamp';
    public const PAYLOAD            = 'payload';
    public const PROCESSED          = 'processed';
    public const PROCESS_MESSAGE    = 'process_message';
    public const RECEIVED_AT        = 'received_at';
    public const PROCESSED_AT       = 'processed_at';

    public function getEntityId(): ?int;
    public function setEntityId(int $entityId): self;

    public function getEventId(): string;
    public function setEventId(string $eventId): self;

    public function getEventType(): string;
    public function setEventType(string $eventType): self;

    public function getEntrega99OrderId(): ?string;
    public function setEntrega99OrderId(?string $orderId): self;

    public function getExternalOrderId(): ?string;
    public function setExternalOrderId(?string $externalOrderId): self;

    public function getEventTimestamp(): ?int;
    public function setEventTimestamp(?int $timestamp): self;

    public function getPayload(): ?string;
    public function setPayload(?string $payload): self;

    public function getProcessed(): bool;
    public function setProcessed(bool $processed): self;

    public function getProcessMessage(): ?string;
    public function setProcessMessage(?string $message): self;

    public function getReceivedAt(): ?string;
    public function setReceivedAt(string $receivedAt): self;

    public function getProcessedAt(): ?string;
    public function setProcessedAt(?string $processedAt): self;
}
