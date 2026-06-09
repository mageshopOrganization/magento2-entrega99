<?php
/**
 * MageShop | Entrega99
 *
 * @category MageShop
 * @package  Entrega99
 */

declare(strict_types=1);

namespace MageShop\Entrega99\Model;

use MageShop\Entrega99\Helper\Data as Helper;
use GuzzleHttp\ClientFactory;
use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * Brazilian CEP (postcode) lookup via ViaCEP.
 *
 * Free, no auth, very reliable. Used at the cart-estimate step where only
 * the postcode is known — enriches with street/neighborhood/city/UF so the
 * downstream geocoder can resolve lat/lng accurately.
 */
class CepLookup
{
    private const ENDPOINT = 'https://viacep.com.br/ws/%s/json/';
    private const CACHE_TAG = 'entrega99_cep';
    private const CACHE_TTL = 2592000; // 30 days — CEPs are stable

    public function __construct(
        private readonly Helper $helper,
        private readonly ClientFactory $httpClientFactory,
        private readonly CacheInterface $cache,
        private readonly SerializerInterface $serializer
    ) {
    }

    /**
     * @return array{cep:string, street:string, neighborhood:string, city:string, region:string}|null
     */
    public function lookup(string $cep): ?array
    {
        $digits = preg_replace('/\D+/', '', $cep) ?? '';
        if (strlen($digits) !== 8) {
            $this->helper->logInfo('CepLookup: invalid CEP length', ['cep' => $cep]);
            return null;
        }

        $cacheKey = 'entrega99_cep_' . $digits;
        $cached = $this->cache->load($cacheKey);
        if ($cached !== false && $cached !== null) {
            try {
                $data = $this->serializer->unserialize($cached);
                if (is_array($data)) {
                    return $data;
                }
            } catch (\Throwable) {
                // re-fetch
            }
        }

        $http = $this->httpClientFactory->create([
            'config' => ['http_errors' => false, 'timeout' => 5],
        ]);

        try {
            $response = $http->get(sprintf(self::ENDPOINT, $digits));
        } catch (GuzzleException $e) {
            $this->helper->logException($e, 'ViaCEP transport error');
            return null;
        }

        if ($response->getStatusCode() !== 200) {
            $this->helper->logInfo('ViaCEP non-200', ['cep' => $digits, 'status' => $response->getStatusCode()]);
            return null;
        }

        $data = json_decode((string)$response->getBody(), true);
        if (!is_array($data) || !empty($data['erro'])) {
            $this->helper->logInfo('ViaCEP: CEP not found', ['cep' => $digits]);
            return null;
        }

        $result = [
            'cep'          => $digits,
            'street'       => (string)($data['logradouro'] ?? ''),
            'neighborhood' => (string)($data['bairro'] ?? ''),
            'city'         => (string)($data['localidade'] ?? ''),
            'region'       => (string)($data['uf'] ?? ''),
        ];

        try {
            $this->cache->save(
                $this->serializer->serialize($result),
                $cacheKey,
                [self::CACHE_TAG],
                self::CACHE_TTL
            );
        } catch (\Throwable $e) {
            $this->helper->logException($e, 'ViaCEP cache save failed');
        }

        return $result;
    }
}
