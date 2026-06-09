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

class WebhookEvent extends AbstractDb
{
    protected function _construct(): void
    {
        $this->_init('mageshop_entrega99_webhook_event', 'entity_id');
    }
}
