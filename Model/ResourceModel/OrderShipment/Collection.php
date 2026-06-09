<?php
/**
 * MageShop | Entrega99
 *
 * @category MageShop
 * @package  Entrega99
 */

declare(strict_types=1);

namespace MageShop\Entrega99\Model\ResourceModel\OrderShipment;

use MageShop\Entrega99\Model\OrderShipment as OrderShipmentModel;
use MageShop\Entrega99\Model\ResourceModel\OrderShipment as OrderShipmentResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'entity_id';

    protected function _construct(): void
    {
        $this->_init(OrderShipmentModel::class, OrderShipmentResource::class);
    }
}
