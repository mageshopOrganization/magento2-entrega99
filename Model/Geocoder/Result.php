<?php
/**
 * MageShop | Entrega99
 *
 * @category MageShop
 * @package  Entrega99
 */

declare(strict_types=1);

namespace MageShop\Entrega99\Model\Geocoder;

/**
 * Immutable geocoding result.
 */
class Result
{
    public function __construct(
        public readonly float $latitude,
        public readonly float $longitude,
        public readonly ?string $formattedAddress = null,
        public readonly string $provider = ''
    ) {
    }

    public function toArray(): array
    {
        return [
            'latitude'          => $this->latitude,
            'longitude'         => $this->longitude,
            'formatted_address' => $this->formattedAddress,
            'provider'          => $this->provider,
        ];
    }
}
