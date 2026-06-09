<?php
/**
 * MageShop | Entrega99
 *
 * @category MageShop
 * @package  Entrega99
 */

declare(strict_types=1);

namespace MageShop\Entrega99\Model\ResourceModel\WebhookEvent;

use MageShop\Entrega99\Model\WebhookEvent as WebhookEventModel;
use MageShop\Entrega99\Model\ResourceModel\WebhookEvent as WebhookEventResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'entity_id';

    protected function _construct(): void
    {
        $this->_init(WebhookEventModel::class, WebhookEventResource::class);
    }
}
