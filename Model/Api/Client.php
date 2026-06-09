<?php
/**
 * MageShop | Entrega99
 *
 * @category MageShop
 * @package  Entrega99
 */

declare(strict_types=1);

namespace MageShop\Entrega99\Model\Api;

use MageShop\Entrega99\Exception\ApiException;
use MageShop\Entrega99\Helper\Data as Helper;
use GuzzleHttp\ClientFactory;
use GuzzleHttp\Exception\GuzzleException;

/**
 * HTTP client wrapper for the 99Entrega API.
 *
 * Responsibilities:
 *  - Obtain Bearer token via Auth and inject it as Authorization header
 *  - Parse the {errno, errmsg, data} response envelope
 *  - Retry once on 401 with a forced token refresh
 *  - Log requests/responses when debug_log is enabled
 *
 * All public methods return the unwrapped `data` array on success.
 * On error (HTTP != 2xx or errno != 0), throws ApiException.
 */
class Client
{
    private const HTTP_TIMEOUT = 30;

    public function __construct(
        private readonly Helper $helper,
        private readonly Auth $auth,
        private readonly ClientFactory $httpClientFactory
    ) {
    }

    /**
     * @throws ApiException
     */
    public function get(string $path, array $query = [], ?int $storeId = null): array
    {
        return $this->request('GET', $path, ['query' => $query], $storeId);
    }

    /**
     * @throws ApiException
     */
    public function post(string $path, array $body = [], ?int $storeId = null): array
    {
        return $this->request('POST', $path, ['json' => $body], $storeId);
    }

    /**
     * @throws ApiException
     */
    private function request(string $method, string $path, array $options, ?int $storeId): array
    {
        $storeId = $this->helper->resolveStoreId($storeId);
        $token = $this->auth->getAccessToken($storeId);

        $response = $this->send($method, $path, $options, $token, $storeId);

        // One retry on 401 with fresh token
        if ($response['status'] === 401) {
            $this->helper->logInfo('99Entrega 401 received — forcing token refresh', ['path' => $path]);
            $token = $this->auth->refresh($storeId);
            $response = $this->send($method, $path, $options, $token, $storeId);
        }

        return $this->unwrap($response, $method, $path);
    }

    /**
     * @return array{status:int, body:string, decoded:?array}
     */
    private function send(string $method, string $path, array $options, string $token, int $storeId): array
    {
        $apiUrl = $this->helper->getApiUrl($storeId);
        if ($apiUrl === '') {
            throw new ApiException(__('99Entrega API URL is not configured.'));
        }

        $http = $this->httpClientFactory->create([
            'config' => [
                'base_uri'    => $apiUrl . '/',
                'http_errors' => false,
                'timeout'     => self::HTTP_TIMEOUT,
            ],
        ]);

        $headers = [
            'Authorization' => 'Bearer ' . $token,
            'Accept'        => 'application/json',
        ];
        if (isset($options['json'])) {
            $headers['Content-Type'] = 'application/json';
        }
        $options['headers'] = array_merge($headers, $options['headers'] ?? []);

        $this->helper->logDebug(sprintf('99Entrega %s %s', $method, $path), [
            'options' => $this->sanitize($options),
        ]);

        try {
            $resp = $http->request($method, ltrim($path, '/'), $options);
        } catch (GuzzleException $e) {
            $this->helper->logException($e, sprintf('99Entrega %s %s transport error', $method, $path));
            throw new ApiException(
                __('99Entrega request failed (%1 %2): %3', $method, $path, $e->getMessage()),
                $e
            );
        }

        $status = $resp->getStatusCode();
        $body = (string)$resp->getBody();
        $decoded = json_decode($body, true);

        $this->helper->logDebug(sprintf('99Entrega %s %s response', $method, $path), [
            'status' => $status,
            'body'   => $body,
        ]);

        return ['status' => $status, 'body' => $body, 'decoded' => is_array($decoded) ? $decoded : null];
    }

    /**
     * Validates the response envelope and returns the `data` payload.
     *
     * @param array{status:int, body:string, decoded:?array} $response
     * @throws ApiException
     */
    private function unwrap(array $response, string $method, string $path): array
    {
        $status = $response['status'];
        $decoded = $response['decoded'];

        if ($decoded === null) {
            throw new ApiException(
                __('99Entrega non-JSON response (HTTP %1) on %2 %3', $status, $method, $path),
                null,
                $status
            );
        }

        $errno = (int)($decoded['errno'] ?? -1);
        $errmsg = (string)($decoded['errmsg'] ?? '');

        if ($status < 200 || $status >= 300 || $errno !== 0) {
            throw new ApiException(
                __('99Entrega API error on %1 %2 (HTTP %3, errno %4): %5', $method, $path, $status, $errno, $errmsg),
                null,
                $errno,
                $decoded
            );
        }

        $data = $decoded['data'] ?? [];
        return is_array($data) ? $data : [];
    }

    /**
     * Removes sensitive content from log payloads.
     */
    private function sanitize(array $options): array
    {
        if (isset($options['headers']['Authorization'])) {
            $options['headers']['Authorization'] = 'Bearer ***';
        }
        return $options;
    }
}
