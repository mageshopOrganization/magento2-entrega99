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
use GuzzleHttp\ClientFactory;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Nominatim (OpenStreetMap) geocoder.
 * Free, rate-limited (1 req/sec), requires a User-Agent.
 */
class Nominatim implements GeocoderInterface
{
    private const ENDPOINT = 'https://nominatim.openstreetmap.org/search';
    private const PROVIDER_CODE = 'nominatim';

    public function __construct(
        private readonly Helper $helper,
        private readonly ClientFactory $httpClientFactory
    ) {
    }

    public function geocode(array $parts, ?int $storeId = null): ?Result
    {
        $userAgent = $this->helper->getNominatimUserAgent($storeId);
        $query = $this->buildQuery($parts);
        if ($query === '') {
            return null;
        }

        $http = $this->httpClientFactory->create([
            'config' => [
                'http_errors' => false,
                'timeout'     => 10,
            ],
        ]);

        try {
            $response = $http->get(self::ENDPOINT, [
                'headers' => [
                    'User-Agent' => $userAgent,
                    'Accept'     => 'application/json',
                ],
                'query' => [
                    'q'              => $query,
                    'format'         => 'json',
                    'limit'          => 1,
                    'addressdetails' => 0,
                    'countrycodes'   => strtolower((string)($parts['country'] ?? 'br')),
                ],
            ]);
        } catch (GuzzleException $e) {
            $this->helper->logException($e, 'Nominatim transport error');
            return null;
        }

        $status = $response->getStatusCode();
        if ($status !== 200) {
            $this->helper->logError('Nominatim non-200 response', ['status' => $status, 'q' => $query]);
            return null;
        }

        $body = (string)$response->getBody();
        $data = json_decode($body, true);
        if (!is_array($data) || empty($data)) {
            $this->helper->logDebug('Nominatim returned no results', ['q' => $query]);
            return null;
        }

        $first = $data[0];
        if (!isset($first['lat'], $first['lon'])) {
            return null;
        }

        return new Result(
            (float)$first['lat'],
            (float)$first['lon'],
            $first['display_name'] ?? null,
            self::PROVIDER_CODE
        );
    }

    private function buildQuery(array $parts): string
    {
        $components = [];
        if (!empty($parts['street'])) {
            $street = trim((string)$parts['street']);
            if (!empty($parts['number'])) {
                $street .= ', ' . trim((string)$parts['number']);
            }
            $components[] = $street;
        }
        if (!empty($parts['neighborhood'])) {
            $components[] = trim((string)$parts['neighborhood']);
        }
        if (!empty($parts['city'])) {
            $components[] = trim((string)$parts['city']);
        }
        if (!empty($parts['region'])) {
            $components[] = trim((string)$parts['region']);
        }
        if (!empty($parts['postcode'])) {
            $components[] = preg_replace('/\D+/', '', (string)$parts['postcode']);
        }
        return implode(', ', array_filter($components));
    }
}
