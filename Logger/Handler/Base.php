<?php
/**
 * MageShop | Entrega99
 *
 * @category MageShop
 * @package  Entrega99
 */

declare(strict_types=1);

namespace MageShop\Entrega99\Logger\Handler;

use Magento\Framework\Logger\Handler\Base as MagentoBaseHandler;
use Monolog\Logger;

class Base extends MagentoBaseHandler
{
    /** @var int */
    protected $loggerType = Logger::DEBUG;

    /** @var string */
    protected $fileName = '/var/log/entrega99.log';
}
