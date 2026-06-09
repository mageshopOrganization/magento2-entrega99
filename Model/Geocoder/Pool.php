<?php
/**
 * MageShop | Entrega99
 *
 * @category MageShop
 * @package  Entrega99
 */

declare(strict_types=1);

namespace MageShop\Entrega99\Model\Geocoder;

use MageShop\Entrega99\Api\GeocoderInterface;
use MageShop\Entrega99\Helper\Data as Helper;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * Dispatches geocode() calls to the provider selected in admin
 * (carriers/entrega99/geocoder_provider). Caches results to avoid hitting
 * Nominatim's 1 req/sec quota and to keep Google billing low.
 *
 * Cache key: entrega99_geo_<provider>_<sha1(normalized address)>
 * TTL: 30 days (geocoding is fairly stable for the same address).
 */
class Pool implements GeocoderInterface
{
    private const CACHE_TAG = 'entrega99_geo';
    private const CACHE_TTL = 2592000; // 30 days

    /**
     * @param array<string, GeocoderInterface> $providers keyed by provider code
     */
    public function __construct(
        private readonly Helper $helper,
        private readonly CacheInterface $cache,
        private readonly SerializerInterface $serializer,
        private readonly array $providers = []
    ) {
    }

    public function geocode(array $parts, ?int $storeId = null): ?Result
    {
        $storeId = $this->helper->resolveStoreId($storeId);
        $providerCode = $this->helper->getGeocoderProvider($storeId);

        if (!isset($this->providers[$providerCode])) {
            $this->helper->logError('Unknown geocoder provider configured', ['provider' => $providerCode]);
            return null;
        }

        $this->helper->logDebug('Geocoder: lookup', ['provider' => $providerCode, 'parts' => $parts]);

        $cacheKey = $this->buildCacheKey($providerCode, $parts);
        $cached = $this->cache->load($cacheKey);
        if ($cached !== false && $cached !== null) {
            try {
                $data = $this->serializer->unserialize($cached);
                if (is_array($data) && isset($data['latitude'], $data['longitude'])) {
                    $this->helper->logInfo('Geocoder: cache hit', [
                        'provider' => $providerCode,
                        'lat'      => $data['latitude'],
                        'lng'      => $data['longitude'],
                    ]);
                    return new Result(
                        (float)$data['latitude'],
                        (float)$data['longitude'],
                        $data['formatted_address'] ?? null,
                        $providerCode . ' (cached)'
                    );
                }
            } catch (\Throwable $e) {
                $this->helper->logException($e, 'Geocoder cache deserialize failed');
            }
        }

        try {
            $result = $this->providers[$providerCode]->geocode($parts, $storeId);
        } catch (LocalizedException $e) {
            $this->helper->logException($e, 'Geocoder provider error: ' . $providerCode);
            return null;
        } catch (\Throwable $e) {
            $this->helper->logException($e, 'Geocoder provider exception: ' . $providerCode);
            return null;
        }

        if ($result === null) {
            $this->helper->logInfo('Geocoder: no result', ['provider' => $providerCode, 'parts' => $parts]);
            return null;
        }

        $this->helper->logInfo('Geocoder: result', [
            'provider' => $providerCode,
            'lat'      => $result->latitude,
            'lng'      => $result->longitude,
        ]);

        try {
            $this->cache->save(
                $this->serializer->serialize($result->toArray()),
                $cacheKey,
                [self::CACHE_TAG],
                self::CACHE_TTL
            );
        } catch (\Throwable $e) {
            $this->helper->logException($e, 'Geocoder cache save failed');
        }

        return $result;
    }

    private function buildCacheKey(string $providerCode, array $parts): string
    {
        $normalized = [
            'street'       => trim((string)($parts['street'] ?? '')),
            'number'       => trim((string)($parts['number'] ?? '')),
            'neighborhood' => trim((string)($parts['neighborhood'] ?? '')),
            'city'         => trim((string)($parts['city'] ?? '')),
            'region'       => trim((string)($parts['region'] ?? '')),
            'postcode'     => preg_replace('/\D+/', '', (string)($parts['postcode'] ?? '')),
            'country'      => strtoupper(trim((string)($parts['country'] ?? 'BR'))),
        ];
        return 'entrega99_geo_' . $providerCode . '_' . sha1($this->serializer->serialize($normalized));
    }
}
