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
 * Google Maps Geocoding API.
 * Requires an API key. Has a free tier (~10k req/month), then paid.
 */
class GoogleMaps implements GeocoderInterface
{
    private const ENDPOINT = 'https://maps.googleapis.com/maps/api/geocode/json';
    private const PROVIDER_CODE = 'google_maps';

    public function __construct(
        private readonly Helper $helper,
        private readonly ClientFactory $httpClientFactory
    ) {
    }

    public function geocode(array $parts, ?int $storeId = null): ?Result
    {
        $apiKey = $this->helper->getGoogleMapsApiKey($storeId);
        if ($apiKey === '') {
            $this->helper->logError('Google Maps API key is not configured');
            return null;
        }

        $address = $this->buildAddress($parts);
        if ($address === '') {
            return null;
        }

        $country = strtoupper((string)($parts['country'] ?? 'BR'));

        $http = $this->httpClientFactory->create([
            'config' => [
                'http_errors' => false,
                'timeout'     => 10,
            ],
        ]);

        try {
            $response = $http->get(self::ENDPOINT, [
                'headers' => ['Accept' => 'application/json'],
                'query' => [
                    'address'    => $address,
                    'components' => 'country:' . $country,
                    'key'        => $apiKey,
                    'language'   => 'pt-BR',
                ],
            ]);
        } catch (GuzzleException $e) {
            $this->helper->logException($e, 'Google Maps transport error');
            return null;
        }

        $status = $response->getStatusCode();
        $body = (string)$response->getBody();
        $data = json_decode($body, true);

        if ($status !== 200 || !is_array($data)) {
            $this->helper->logError('Google Maps non-200 / non-JSON', ['status' => $status]);
            return null;
        }

        if (($data['status'] ?? '') !== 'OK' || empty($data['results'])) {
            $this->helper->logDebug('Google Maps no results', [
                'status' => $data['status'] ?? '',
                'error'  => $data['error_message'] ?? null,
                'q'      => $address,
            ]);
            return null;
        }

        $first = $data['results'][0];
        $location = $first['geometry']['location'] ?? null;
        if (!isset($location['lat'], $location['lng'])) {
            return null;
        }

        return new Result(
            (float)$location['lat'],
            (float)$location['lng'],
            $first['formatted_address'] ?? null,
            self::PROVIDER_CODE
        );
    }

    private function buildAddress(array $parts): string
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
