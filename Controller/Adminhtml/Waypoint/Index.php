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
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\Page;

class Index extends Waypoint implements HttpGetActionInterface
{
    public function execute(): Page
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu(self::ADMIN_RESOURCE);
        $resultPage->getConfig()->getTitle()->prepend(__('99Entrega Pickup Locations'));
        $resultPage->addBreadcrumb(__('Stores'), __('Stores'));
        $resultPage->addBreadcrumb(__('Pickup Locations'), __('Pickup Locations'));
        return $resultPage;
    }
}
