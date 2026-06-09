<?php
/**
 * MageShop | Entrega99
 *
 * @category MageShop
 * @package  Entrega99
 */

declare(strict_types=1);

namespace MageShop\Entrega99\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * 99Entrega return_handover_method:
 *  1 = none, 2 = photo, 3 = code
 */
class HandoverMethod implements OptionSourceInterface
{
    public const NONE = 1;
    public const PHOTO = 2;
    public const CODE = 3;

    public function toOptionArray(): array
    {
        return [
            ['value' => self::NONE, 'label' => __('None')],
            ['value' => self::PHOTO, 'label' => __('Photo')],
            ['value' => self::CODE, 'label' => __('Code')],
        ];
    }
}
