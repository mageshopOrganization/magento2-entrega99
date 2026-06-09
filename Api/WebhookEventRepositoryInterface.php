<?php
/**
 * MageShop | Entrega99
 *
 * @category MageShop
 * @package  Entrega99
 */

declare(strict_types=1);

namespace MageShop\Entrega99\Api;

use MageShop\Entrega99\Api\Data\WebhookEventInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

interface WebhookEventRepositoryInterface
{
    /**
     * @throws CouldNotSaveException
     */
    public function save(WebhookEventInterface $event): WebhookEventInterface;

    /**
     * @throws NoSuchEntityException
     */
    public function getById(int $entityId): WebhookEventInterface;

    /**
     * Lookup by the idempotency key sent by 99. Returns null if never seen.
     */
    public function findByEventId(string $eventId): ?WebhookEventInterface;
}
