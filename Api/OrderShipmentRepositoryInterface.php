<?php
/**
 * MageShop | Entrega99
 *
 * @category MageShop
 * @package  Entrega99
 */

declare(strict_types=1);

namespace MageShop\Entrega99\Api;

use MageShop\Entrega99\Api\Data\OrderShipmentInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

interface OrderShipmentRepositoryInterface
{
    /**
     * @throws CouldNotSaveException
     */
    public function save(OrderShipmentInterface $shipment): OrderShipmentInterface;

    /**
     * @throws NoSuchEntityException
     */
    public function getById(int $entityId): OrderShipmentInterface;

    /**
     * @throws NoSuchEntityException
     */
    public function getByOrderId(int $orderId): OrderShipmentInterface;

    /**
     * Returns null if not found. Used by the webhook handler to look up the Magento order
     * given a 99 order_id or external_order_id from the event payload.
     */
    public function findByEntrega99OrderId(string $entrega99OrderId): ?OrderShipmentInterface;

    public function findByExternalOrderId(string $externalOrderId): ?OrderShipmentInterface;

    /**
     * @throws CouldNotDeleteException
     */
    public function delete(OrderShipmentInterface $shipment): bool;
}
