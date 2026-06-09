<?php
/**
 * MageShop | Entrega99
 *
 * @category MageShop
 * @package  Entrega99
 */

declare(strict_types=1);

namespace MageShop\Entrega99\Api\Data;

interface OrderShipmentInterface
{
    public const ENTITY_ID          = 'entity_id';
    public const ORDER_ID           = 'order_id';
    public const STORE_ID           = 'store_id';
    public const INCREMENT_ID       = 'increment_id';
    public const WAYPOINT_ID        = 'waypoint_id';
    public const EXTERNAL_ORDER_ID  = 'external_order_id';
    public const ENTREGA99_ORDER_ID = 'entrega99_order_id';
    public const ESTIMATE_ID        = 'estimate_id';
    public const VEHICLE_TYPE       = 'vehicle_type';
    public const STATUS             = 'status';
    public const TRACKING_LINK      = 'tracking_link';
    public const DRIVER_INFO        = 'driver_info';
    public const PICKUP_CODE        = 'pickup_code';
    public const DROPOFF_CODE       = 'dropoff_code';
    public const FEE_CENTS          = 'fee_cents';
    public const CURRENCY           = 'currency';
    public const CREATED_AT         = 'created_at';
    public const UPDATED_AT         = 'updated_at';

    // Local sync statuses — internal, separate from Magento order state
    public const STATUS_PENDING              = 'pending';      // placeholder, not yet sent
    public const STATUS_CREATED              = 'created';      // /order/create accepted
    public const STATUS_FINDING              = 'finding';      // 99 looking for driver
    public const STATUS_WAITING              = 'waiting';      // waiting for driver
    public const STATUS_DRIVER_ACCEPTED      = 'driver_accepted';
    public const STATUS_DRIVER_ARRIVED       = 'driver_arrived';
    public const STATUS_DELIVERING           = 'delivering';
    public const STATUS_COMPLETED            = 'completed';
    public const STATUS_CANCELED             = 'canceled';
    public const STATUS_CLOSED               = 'closed';
    public const STATUS_SENDBACK             = 'sendback';
    public const STATUS_SENDBACK_COMPLETED   = 'sendback_completed';
    public const STATUS_FAILED               = 'failed';       // creation failed

    public function getEntityId(): ?int;
    public function setEntityId(int $entityId): self;

    public function getOrderId(): int;
    public function setOrderId(int $orderId): self;

    public function getStoreId(): ?int;
    public function setStoreId(?int $storeId): self;

    public function getIncrementId(): string;
    public function setIncrementId(string $incrementId): self;

    public function getWaypointId(): ?int;
    public function setWaypointId(?int $waypointId): self;

    public function getExternalOrderId(): ?string;
    public function setExternalOrderId(?string $externalOrderId): self;

    public function getEntrega99OrderId(): ?string;
    public function setEntrega99OrderId(?string $orderId): self;

    public function getEstimateId(): ?string;
    public function setEstimateId(?string $estimateId): self;

    public function getVehicleType(): ?string;
    public function setVehicleType(?string $vehicleType): self;

    public function getStatus(): string;
    public function setStatus(string $status): self;

    public function getTrackingLink(): ?string;
    public function setTrackingLink(?string $trackingLink): self;

    public function getDriverInfo(): ?string;
    public function setDriverInfo(?string $driverInfoJson): self;

    public function getPickupCode(): ?string;
    public function setPickupCode(?string $pickupCode): self;

    public function getDropoffCode(): ?string;
    public function setDropoffCode(?string $dropoffCode): self;

    public function getFeeCents(): ?int;
    public function setFeeCents(?int $feeCents): self;

    public function getCurrency(): ?string;
    public function setCurrency(?string $currency): self;

    public function getCreatedAt(): ?string;
    public function setCreatedAt(string $createdAt): self;

    public function getUpdatedAt(): ?string;
    public function setUpdatedAt(string $updatedAt): self;
}
