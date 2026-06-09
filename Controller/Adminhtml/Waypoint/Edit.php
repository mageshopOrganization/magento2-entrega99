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
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;

class Edit extends Waypoint implements HttpGetActionInterface
{
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \MageShop\Entrega99\Api\WaypointRepositoryInterface $waypointRepository,
        \MageShop\Entrega99\Api\Data\WaypointInterfaceFactory $waypointFactory,
        private readonly Registry $coreRegistry
    ) {
        parent::__construct($context, $resultPageFactory, $waypointRepository, $waypointFactory);
    }

    public function execute(): ResultInterface
    {
        $waypointId = (int)$this->getRequest()->getParam('waypoint_id');
        $waypoint = $this->waypointFactory->create();

        if ($waypointId) {
            try {
                $waypoint = $this->waypointRepository->getById($waypointId);
            } catch (NoSuchEntityException $e) {
                $this->messageManager->addErrorMessage(__('This waypoint no longer exists.'));
                /** @var Redirect $redirect */
                $redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                return $redirect->setPath('*/*/');
            }
        }

        $this->coreRegistry->register('entrega99_waypoint', $waypoint);

        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu(self::ADMIN_RESOURCE);
        $resultPage->getConfig()->getTitle()->prepend(
            $waypointId ? __('Edit Pickup Location') : __('New Pickup Location')
        );
        return $resultPage;
    }
}
