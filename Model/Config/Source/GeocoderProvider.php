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

class GeocoderProvider implements OptionSourceInterface
{
    public const NOMINATIM = 'nominatim';
    public const GOOGLE_MAPS = 'google_maps';

    public function toOptionArray(): array
    {
        return [
            ['value' => self::NOMINATIM, 'label' => __('Nominatim (OpenStreetMap, free)')],
            ['value' => self::GOOGLE_MAPS, 'label' => __('Google Maps Geocoding (paid)')],
        ];
    }
}
