<?php
/**
 * MageShop | Entrega99
 *
 * @category MageShop
 * @package  Entrega99
 */

declare(strict_types=1);

namespace MageShop\Entrega99\Model\ResourceModel\Token;

use MageShop\Entrega99\Model\Token as TokenModel;
use MageShop\Entrega99\Model\ResourceModel\Token as TokenResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'entity_id';

    protected function _construct(): void
    {
        $this->_init(TokenModel::class, TokenResource::class);
    }
}
