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
use MageShop\Entrega99\Api\Data\WebhookEventInterfaceFactory;
use MageShop\Entrega99\Api\WebhookEventRepositoryInterface;
use MageShop\Entrega99\Model\ResourceModel\WebhookEvent as WebhookEventResource;
use MageShop\Entrega99\Model\ResourceModel\WebhookEvent\CollectionFactory;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class WebhookEventRepository implements WebhookEventRepositoryInterface
{
    public function __construct(
        private readonly WebhookEventResource $resource,
        private readonly WebhookEventInterfaceFactory $eventFactory,
        private readonly CollectionFactory $collectionFactory
    ) {
    }

    public function save(WebhookEventInterface $event): WebhookEventInterface
    {
        try {
            $this->resource->save($event);
        } catch (\Throwable $e) {
            throw new CouldNotSaveException(__('Could not save webhook event: %1', $e->getMessage()), $e);
        }
        return $event;
    }

    public function getById(int $entityId): WebhookEventInterface
    {
        /** @var WebhookEvent $event */
        $event = $this->eventFactory->create();
        $this->resource->load($event, $entityId);
        if (!$event->getEntityId()) {
            throw new NoSuchEntityException(__('Webhook event with id %1 was not found.', $entityId));
        }
        return $event;
    }

    public function findByEventId(string $eventId): ?WebhookEventInterface
    {
        $collection = $this->collectionFactory->create()
            ->addFieldToFilter('event_id', $eventId)
            ->setPageSize(1);

        /** @var WebhookEvent|false $event */
        $event = $collection->getFirstItem();
        return $event && $event->getEntityId() ? $event : null;
    }
}
