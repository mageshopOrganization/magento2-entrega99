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
use MageShop\Entrega99\Api\OrderShipmentRepositoryInterface;
use MageShop\Entrega99\Model\ResourceModel\OrderShipment as OrderShipmentResource;
use MageShop\Entrega99\Model\ResourceModel\OrderShipment\CollectionFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class OrderShipmentRepository implements OrderShipmentRepositoryInterface
{
    public function __construct(
        private readonly OrderShipmentResource $resource,
        private readonly OrderShipmentInterfaceFactory $shipmentFactory,
        private readonly CollectionFactory $collectionFactory
    ) {
    }

    public function save(OrderShipmentInterface $shipment): OrderShipmentInterface
    {
        try {
            $this->resource->save($shipment);
        } catch (\Throwable $e) {
            throw new CouldNotSaveException(__('Could not save order shipment: %1', $e->getMessage()), $e);
        }
        return $shipment;
    }

    public function getById(int $entityId): OrderShipmentInterface
    {
        /** @var OrderShipment $shipment */
        $shipment = $this->shipmentFactory->create();
        $this->resource->load($shipment, $entityId);
        if (!$shipment->getEntityId()) {
            throw new NoSuchEntityException(__('Order shipment with id %1 was not found.', $entityId));
        }
        return $shipment;
    }

    public function getByOrderId(int $orderId): OrderShipmentInterface
    {
        /** @var OrderShipment $shipment */
        $shipment = $this->shipmentFactory->create();
        $this->resource->load($shipment, $orderId, 'order_id');
        if (!$shipment->getEntityId()) {
            throw new NoSuchEntityException(__('Order shipment for order_id %1 was not found.', $orderId));
        }
        return $shipment;
    }

    public function findByEntrega99OrderId(string $entrega99OrderId): ?OrderShipmentInterface
    {
        $collection = $this->collectionFactory->create()
            ->addFieldToFilter('entrega99_order_id', $entrega99OrderId)
            ->setPageSize(1);

        /** @var OrderShipment|false $shipment */
        $shipment = $collection->getFirstItem();
        return $shipment && $shipment->getEntityId() ? $shipment : null;
    }

    public function findByExternalOrderId(string $externalOrderId): ?OrderShipmentInterface
    {
        $collection = $this->collectionFactory->create()
            ->addFieldToFilter('external_order_id', $externalOrderId)
            ->setPageSize(1);

        /** @var OrderShipment|false $shipment */
        $shipment = $collection->getFirstItem();
        return $shipment && $shipment->getEntityId() ? $shipment : null;
    }

    public function delete(OrderShipmentInterface $shipment): bool
    {
        try {
            $this->resource->delete($shipment);
        } catch (\Throwable $e) {
            throw new CouldNotDeleteException(__('Could not delete order shipment: %1', $e->getMessage()), $e);
        }
        return true;
    }
}
