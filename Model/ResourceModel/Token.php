<?php
/**
 * MageShop | Entrega99
 *
 * @category MageShop
 * @package  Entrega99
 */

declare(strict_types=1);

namespace MageShop\Entrega99\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Token extends AbstractDb
{
    protected function _construct(): void
    {
        $this->_init('mageshop_entrega99_token', 'entity_id');
    }
}
