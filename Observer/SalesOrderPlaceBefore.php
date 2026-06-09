<?php
/**
 * MageShop | Entrega99
 *
 * @category MageShop
 * @package  Entrega99
 */

declare(strict_types=1);

namespace MageShop\Entrega99\Observer;

use MageShop\Entrega99\Helper\Data as Helper;
use MageShop\Entrega99\Model\Carrier\Entrega99 as CarrierConsts;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Captures the 99Entrega session context (waypoint, vehicle, estimate) and
 * attaches it to the order so SalesOrderSaveAfter can read it without
 * depending on the HTTP checkout session (which may not be available in
 * async/webhook contexts).
 */
class SalesOrderPlaceBefore implements ObserverInterface
{
    private const SHIPPING_METHOD = CarrierConsts::CARRIER_CODE . '_' . CarrierConsts::METHOD_CODE;

    public function __construct(
        private readonly Helper $helper,
        private readonly CheckoutSession $checkoutSession
    ) {
    }

    public function execute(Observer $observer): void
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();
        if (!$order || $order->getShippingMethod() !== self::SHIPPING_METHOD) {
            return;
        }

        try {
            $waypointId   = $this->checkoutSession->getEntrega99WaypointId();
            $vehicleType  = $this->checkoutSession->getEntrega99VehicleType();
            $estimateId   = $this->checkoutSession->getEntrega99EstimateId();
            $feeCents     = $this->checkoutSession->getEntrega99FeeCents();
            $currency     = $this->checkoutSession->getEntrega99Currency();

            $order->setData('entrega99_waypoint_id', $waypointId !== null ? (int)$waypointId : null);
            $order->setData('entrega99_vehicle_type', $vehicleType);
            $order->setData('entrega99_estimate_id', $estimateId);
            $order->setData('entrega99_fee_cents', $feeCents !== null ? (int)$feeCents : null);
            $order->setData('entrega99_currency', $currency);
        } catch (\Throwable $e) {
            $this->helper->logException($e, 'SalesOrderPlaceBefore failed');
        }
    }
}
