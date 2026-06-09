<?php
/**
 * MageShop | Entrega99
 *
 * @category MageShop
 * @package  Entrega99
 */

declare(strict_types=1);

namespace MageShop\Entrega99\Api;

use MageShop\Entrega99\Model\Geocoder\Result;

interface GeocoderInterface
{
    /**
     * Converts a textual address into latitude/longitude.
     * Returns null when the address cannot be resolved.
     *
     * @param array{
     *     street?: string,
     *     number?: string,
     *     city?: string,
     *     region?: string,
     *     postcode?: string,
     *     country?: string,
     *     neighborhood?: string
     * } $parts
     */
    public function geocode(array $parts, ?int $storeId = null): ?Result;
}
