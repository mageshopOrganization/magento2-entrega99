<?php
/**
 * MageShop | Entrega99
 *
 * @category MageShop
 * @package  Entrega99
 */

declare(strict_types=1);

namespace MageShop\Entrega99\Model\ResourceModel\Waypoint;

use MageShop\Entrega99\Model\Waypoint as WaypointModel;
use MageShop\Entrega99\Model\ResourceModel\Waypoint as WaypointResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'waypoint_id';

    protected function _construct(): void
    {
        $this->_init(WaypointModel::class, WaypointResource::class);
    }
}
