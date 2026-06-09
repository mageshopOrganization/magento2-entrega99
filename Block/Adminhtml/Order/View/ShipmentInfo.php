<?php
/**
 * MageShop | Entrega99
 *
 * @category MageShop
 * @package  Entrega99
 */

declare(strict_types=1);

namespace MageShop\Entrega99\Block\Adminhtml\Order\View;

use MageShop\Entrega99\Api\Data\OrderShipmentInterface;
use MageShop\Entrega99\Api\OrderShipmentRepositoryInterface;
use MageShop\Entrega99\Model\Carrier\Entrega99 as CarrierConsts;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Sales\Model\Order;

/**
 * Renders the 99Entrega panel on the order view page with status + driver info
 * + Create/Cancel buttons.
 */
class ShipmentInfo extends Template
{
    public function __construct(
        Context $context,
        private readonly Registry $coreRegistry,
        private readonly OrderShipmentRepositoryInterface $orderShipmentRepository,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getOrder(): ?Order
    {
        return $this->coreRegistry->registry('current_order')
            ?: $this->coreRegistry->registry('sales_order');
    }

    public function isEntrega99Order(): bool
    {
        $order = $this->getOrder();
        if (!$order) {
            return false;
        }
        return $order->getShippingMethod() === CarrierConsts::CARRIER_CODE . '_' . CarrierConsts::METHOD_CODE;
    }

    public function getShipment(): ?OrderShipmentInterface
    {
        $order = $this->getOrder();
        if (!$order || !$order->getId()) {
            return null;
        }
        try {
            return $this->orderShipmentRepository->getByOrderId((int)$order->getId());
        } catch (\Throwable) {
            return null;
        }
    }

    public function canCreate(?OrderShipmentInterface $shipment): bool
    {
        if ($shipment === null) {
            return true;
        }
        return in_array($shipment->getStatus(), [
            OrderShipmentInterface::STATUS_PENDING,
            OrderShipmentInterface::STATUS_FAILED,
            OrderShipmentInterface::STATUS_CANCELED,
        ], true) && empty($shipment->getEntrega99OrderId());
    }

    public function canCancel(?OrderShipmentInterface $shipment): bool
    {
        if ($shipment === null) {
            return false;
        }
        if (empty($shipment->getEntrega99OrderId())) {
            return false;
        }
        return !in_array($shipment->getStatus(), [
            OrderShipmentInterface::STATUS_COMPLETED,
            OrderShipmentInterface::STATUS_CANCELED,
            OrderShipmentInterface::STATUS_CLOSED,
            OrderShipmentInterface::STATUS_SENDBACK_COMPLETED,
        ], true);
    }

    public function getCreateUrl(): string
    {
        $order = $this->getOrder();
        return $order ? $this->getUrl('entrega99/shipment/create', ['order_id' => $order->getId()]) : '';
    }

    public function getCancelUrl(): string
    {
        $order = $this->getOrder();
        return $order ? $this->getUrl('entrega99/shipment/cancel', ['order_id' => $order->getId()]) : '';
    }

    public function getDriverInfoAsArray(?OrderShipmentInterface $shipment): array
    {
        if ($shipment === null || empty($shipment->getDriverInfo())) {
            return [];
        }
        $decoded = json_decode((string)$shipment->getDriverInfo(), true);
        return is_array($decoded) ? $decoded : [];
    }
}
