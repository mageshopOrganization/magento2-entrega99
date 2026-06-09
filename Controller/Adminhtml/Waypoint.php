<?php
/**
 * MageShop | Entrega99
 *
 * @category MageShop
 * @package  Entrega99
 */

declare(strict_types=1);

namespace MageShop\Entrega99\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use MageShop\Entrega99\Api\Data\WaypointInterfaceFactory;
use MageShop\Entrega99\Api\WaypointRepositoryInterface;

abstract class Waypoint extends Action
{
    public const ADMIN_RESOURCE = 'MageShop_Entrega99::waypoint';

    public function __construct(
        Context $context,
        protected readonly PageFactory $resultPageFactory,
        protected readonly WaypointRepositoryInterface $waypointRepository,
        protected readonly WaypointInterfaceFactory $waypointFactory
    ) {
        parent::__construct($context);
    }
}
