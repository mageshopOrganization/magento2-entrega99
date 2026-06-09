<?php
/**
 * MageShop | Entrega99
 *
 * @category MageShop
 * @package  Entrega99
 */

declare(strict_types=1);

namespace MageShop\Entrega99\Controller\Adminhtml\Waypoint;

use MageShop\Entrega99\Controller\Adminhtml\Waypoint;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;

class Delete extends Waypoint implements HttpPostActionInterface
{
    public function execute(): ResultInterface
    {
        /** @var Redirect $redirect */
        $redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        $waypointId = (int)$this->getRequest()->getParam('waypoint_id');
        if (!$waypointId) {
            return $redirect->setPath('*/*/');
        }

        try {
            $this->waypointRepository->deleteById($waypointId);
            $this->messageManager->addSuccessMessage(__('Pickup location deleted.'));
        } catch (\Throwable $e) {
            $this->messageManager->addErrorMessage(__('Could not delete: %1', $e->getMessage()));
        }
        return $redirect->setPath('*/*/');
    }
}
