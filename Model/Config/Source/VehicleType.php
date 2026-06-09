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

class VehicleType implements OptionSourceInterface
{
    public const MOTO = 'entrega_moto';
    public const CAR = 'entrega_car';

    public function toOptionArray(): array
    {
        return [
            ['value' => self::MOTO, 'label' => __('Motorcycle (entrega_moto)')],
            ['value' => self::CAR, 'label' => __('Car (entrega_car)')],
        ];
    }
}
