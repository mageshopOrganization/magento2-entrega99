<?php
/**
 * MageShop | Entrega99
 *
 * @category MageShop
 * @package  Entrega99
 */

declare(strict_types=1);

namespace MageShop\Entrega99\Controller\Adminhtml\Shipment;

use MageShop\Entrega99\Model\CreateShipment;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;

class Create extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'MageShop_Entrega99::shipment_create';

    public function __construct(
        Context $context,
        private readonly CreateShipment $createShipment
    ) {
        parent::__construct($context);
    }

    public function execute(): ResultInterface
    {
        /** @var Redirect $redirect */
        $redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        $orderId = (int)$this->getRequest()->getParam('order_id');
        if (!$orderId) {
            $this->messageManager->addErrorMessage(__('Missing order_id.'));
            return $redirect->setPath('sales/order/index');
        }

        try {
            $shipment = $this->createShipment->create($orderId);
            $this->messageManager->addSuccessMessage(
                __('99Entrega delivery created: %1', $shipment->getEntrega99OrderId())
            );
        } catch (\Throwable $e) {
            $this->messageManager->addErrorMessage(
                __('Failed to create 99Entrega delivery: %1', $e->getMessage())
            );
        }

        return $redirect->setPath('sales/order/view', ['order_id' => $orderId]);
    }
}
