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
use MageShop\Entrega99\Model\ResourceModel\OrderShipment as OrderShipmentResource;
use Magento\Framework\Model\AbstractModel;

class OrderShipment extends AbstractModel implements OrderShipmentInterface
{
    protected $_eventPrefix = 'mageshop_entrega99_order_shipment';

    protected function _construct(): void
    {
        $this->_init(OrderShipmentResource::class);
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

    public function getOrderId(): int
    {
        return (int)$this->getData(self::ORDER_ID);
    }

    public function setOrderId(int $orderId): self
    {
        return $this->setData(self::ORDER_ID, $orderId);
    }

    public function getStoreId(): ?int
    {
        $value = $this->getData(self::STORE_ID);
        return $value === null ? null : (int)$value;
    }

    public function setStoreId(?int $storeId): self
    {
        return $this->setData(self::STORE_ID, $storeId);
    }

    public function getIncrementId(): string
    {
        return (string)$this->getData(self::INCREMENT_ID);
    }

    public function setIncrementId(string $incrementId): self
    {
        return $this->setData(self::INCREMENT_ID, $incrementId);
    }

    public function getWaypointId(): ?int
    {
        $value = $this->getData(self::WAYPOINT_ID);
        return $value === null ? null : (int)$value;
    }

    public function setWaypointId(?int $waypointId): self
    {
        return $this->setData(self::WAYPOINT_ID, $waypointId);
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

    public function getEntrega99OrderId(): ?string
    {
        $value = $this->getData(self::ENTREGA99_ORDER_ID);
        return $value === null ? null : (string)$value;
    }

    public function setEntrega99OrderId(?string $orderId): self
    {
        return $this->setData(self::ENTREGA99_ORDER_ID, $orderId);
    }

    public function getEstimateId(): ?string
    {
        $value = $this->getData(self::ESTIMATE_ID);
        return $value === null ? null : (string)$value;
    }

    public function setEstimateId(?string $estimateId): self
    {
        return $this->setData(self::ESTIMATE_ID, $estimateId);
    }

    public function getVehicleType(): ?string
    {
        $value = $this->getData(self::VEHICLE_TYPE);
        return $value === null ? null : (string)$value;
    }

    public function setVehicleType(?string $vehicleType): self
    {
        return $this->setData(self::VEHICLE_TYPE, $vehicleType);
    }

    public function getStatus(): string
    {
        return (string)$this->getData(self::STATUS);
    }

    public function setStatus(string $status): self
    {
        return $this->setData(self::STATUS, $status);
    }

    public function getTrackingLink(): ?string
    {
        $value = $this->getData(self::TRACKING_LINK);
        return $value === null ? null : (string)$value;
    }

    public function setTrackingLink(?string $trackingLink): self
    {
        return $this->setData(self::TRACKING_LINK, $trackingLink);
    }

    public function getDriverInfo(): ?string
    {
        $value = $this->getData(self::DRIVER_INFO);
        return $value === null ? null : (string)$value;
    }

    public function setDriverInfo(?string $driverInfoJson): self
    {
        return $this->setData(self::DRIVER_INFO, $driverInfoJson);
    }

    public function getPickupCode(): ?string
    {
        $value = $this->getData(self::PICKUP_CODE);
        return $value === null ? null : (string)$value;
    }

    public function setPickupCode(?string $pickupCode): self
    {
        return $this->setData(self::PICKUP_CODE, $pickupCode);
    }

    public function getDropoffCode(): ?string
    {
        $value = $this->getData(self::DROPOFF_CODE);
        return $value === null ? null : (string)$value;
    }

    public function setDropoffCode(?string $dropoffCode): self
    {
        return $this->setData(self::DROPOFF_CODE, $dropoffCode);
    }

    public function getFeeCents(): ?int
    {
        $value = $this->getData(self::FEE_CENTS);
        return $value === null ? null : (int)$value;
    }

    public function setFeeCents(?int $feeCents): self
    {
        return $this->setData(self::FEE_CENTS, $feeCents);
    }

    public function getCurrency(): ?string
    {
        $value = $this->getData(self::CURRENCY);
        return $value === null ? null : (string)$value;
    }

    public function setCurrency(?string $currency): self
    {
        return $this->setData(self::CURRENCY, $currency);
    }

    public function getCreatedAt(): ?string
    {
        $value = $this->getData(self::CREATED_AT);
        return $value === null ? null : (string)$value;
    }

    public function setCreatedAt(string $createdAt): self
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    public function getUpdatedAt(): ?string
    {
        $value = $this->getData(self::UPDATED_AT);
        return $value === null ? null : (string)$value;
    }

    public function setUpdatedAt(string $updatedAt): self
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }
}
