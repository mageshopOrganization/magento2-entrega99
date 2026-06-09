<?php
/**
 * MageShop | Entrega99
 *
 * @category MageShop
 * @package  Entrega99
 */

declare(strict_types=1);

namespace MageShop\Entrega99\Model;

use MageShop\Entrega99\Api\Data\WebhookEventInterface;
use MageShop\Entrega99\Model\ResourceModel\WebhookEvent as WebhookEventResource;
use Magento\Framework\Model\AbstractModel;

class WebhookEvent extends AbstractModel implements WebhookEventInterface
{
    protected $_eventPrefix = 'mageshop_entrega99_webhook_event';

    protected function _construct(): void
    {
        $this->_init(WebhookEventResource::class);
    }

    public function getEntityId(): ?int
    {
        $value = $this->getData(self::ENTITY_ID);
        return $value === null ? null : (int)$value;
    }

    public function setEntityId($entityId): self
    {
        return $this->setData(self::ENTITY_ID, (int)$entityId);
    }

    public function getEventId(): string
    {
        return (string)$this->getData(self::EVENT_ID);
    }

    public function setEventId(string $eventId): self
    {
        return $this->setData(self::EVENT_ID, $eventId);
    }

    public function getEventType(): string
    {
        return (string)$this->getData(self::EVENT_TYPE);
    }

    public function setEventType(string $eventType): self
    {
        return $this->setData(self::EVENT_TYPE, $eventType);
    }

    public function getEntrega99OrderId(): ?string
    {
        $value = $this->getData(self::ENTREGA99_ORDER_ID);
        return $value === null ? null : (string)$value;
    }

    public function setEntrega99OrderId(?string $orderId): self
    {
        return $this->setData(self::ENTREGA99_ORDER_ID, $orderId);
    }

    public function getExternalOrderId(): ?string
    {
        $value = $this->getData(self::EXTERNAL_ORDER_ID);
        return $value === null ? null : (string)$value;
    }

    public function setExternalOrderId(?string $externalOrderId): self
    {
        return $this->setData(self::EXTERNAL_ORDER_ID, $externalOrderId);
    }

    public function getEventTimestamp(): ?int
    {
        $value = $this->getData(self::EVENT_TIMESTAMP);
        return $value === null ? null : (int)$value;
    }

    public function setEventTimestamp(?int $timestamp): self
    {
        return $this->setData(self::EVENT_TIMESTAMP, $timestamp);
    }

    public function getPayload(): ?string
    {
        $value = $this->getData(self::PAYLOAD);
        return $value === null ? null : (string)$value;
    }

    public function setPayload(?string $payload): self
    {
        return $this->setData(self::PAYLOAD, $payload);
    }

    public function getProcessed(): bool
    {
        return (bool)$this->getData(self::PROCESSED);
    }

    public function setProcessed(bool $processed): self
    {
        return $this->setData(self::PROCESSED, $processed);
    }

    public function getProcessMessage(): ?string
    {
        $value = $this->getData(self::PROCESS_MESSAGE);
        return $value === null ? null : (string)$value;
    }

    public function setProcessMessage(?string $message): self
    {
        return $this->setData(self::PROCESS_MESSAGE, $message);
    }

    public function getReceivedAt(): ?string
    {
        $value = $this->getData(self::RECEIVED_AT);
        return $value === null ? null : (string)$value;
    }

    public function setReceivedAt(string $receivedAt): self
    {
        return $this->setData(self::RECEIVED_AT, $receivedAt);
    }

    public function getProcessedAt(): ?string
    {
        $value = $this->getData(self::PROCESSED_AT);
        return $value === null ? null : (string)$value;
    }

    public function setProcessedAt(?string $processedAt): self
    {
        return $this->setData(self::PROCESSED_AT, $processedAt);
    }
}
