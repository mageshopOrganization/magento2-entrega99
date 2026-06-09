<?php
/**
 * MageShop | Entrega99
 *
 * @category MageShop
 * @package  Entrega99
 */

declare(strict_types=1);

namespace MageShop\Entrega99\Model\Api;

use MageShop\Entrega99\Api\Data\TokenInterface;
use MageShop\Entrega99\Api\Data\TokenInterfaceFactory;
use MageShop\Entrega99\Api\TokenRepositoryInterface;
use MageShop\Entrega99\Exception\ApiException;
use MageShop\Entrega99\Helper\Data as Helper;
use GuzzleHttp\ClientFactory;
use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\Lock\LockManagerInterface;

/**
 * OAuth 2.0 client_credentials flow with persistent token cache.
 *
 * Token endpoint: POST {api_url}/oauth/v2/token
 * Cached in the mageshop_entrega99_token table per (store_id, scope).
 * Proactively refreshed when less than REFRESH_SKEW_SECONDS remain before expiry.
 */
class Auth
{
    private const TOKEN_PATH = '/oauth/v2/token';
    private const REFRESH_SKEW_SECONDS = 300;   // 5 min
    private const LOCK_TIMEOUT_SECONDS = 5;

    public function __construct(
        private readonly Helper $helper,
        private readonly TokenRepositoryInterface $tokenRepository,
        private readonly TokenInterfaceFactory $tokenFactory,
        private readonly ClientFactory $httpClientFactory,
        private readonly LockManagerInterface $lockManager
    ) {
    }

    /**
     * Returns a valid Bearer access token, refreshing/issuing one if needed.
     *
     * @throws ApiException
     */
    public function getAccessToken(?int $storeId = null): string
    {
        $storeId = $this->helper->resolveStoreId($storeId);
        $scope = $this->helper->getTokenScope($storeId);

        $cached = $this->tokenRepository->findByStoreAndScope($storeId, $scope);
        if ($cached && !$this->isExpiringSoon($cached)) {
            return $cached->getAccessToken();
        }

        // Lock to avoid concurrent refreshes from multiple workers.
        $lockName = sprintf('entrega99_token_%d_%s', $storeId, $scope);
        $locked = false;
        try {
            $locked = $this->lockManager->lock($lockName, self::LOCK_TIMEOUT_SECONDS);
        } catch (\Throwable $e) {
            $this->helper->logException($e, 'Token lock acquire failed (continuing without lock)');
        }

        try {
            // Re-check after lock: another worker may have refreshed in the meantime.
            $cached = $this->tokenRepository->findByStoreAndScope($storeId, $scope);
            if ($cached && !$this->isExpiringSoon($cached)) {
                return $cached->getAccessToken();
            }

            return $this->issueAndPersist($storeId, $scope, $cached)->getAccessToken();
        } finally {
            if ($locked) {
                try {
                    $this->lockManager->unlock($lockName);
                } catch (\Throwable $e) {
                    $this->helper->logException($e, 'Token lock release failed');
                }
            }
        }
    }

    /**
     * Forces a fresh token, ignoring cache. Used by Client on 401.
     *
     * @throws ApiException
     */
    public function refresh(?int $storeId = null): string
    {
        $storeId = $this->helper->resolveStoreId($storeId);
        $scope = $this->helper->getTokenScope($storeId);
        $cached = $this->tokenRepository->findByStoreAndScope($storeId, $scope);
        return $this->issueAndPersist($storeId, $scope, $cached)->getAccessToken();
    }

    private function isExpiringSoon(TokenInterface $token): bool
    {
        // expires_at is written with gmdate() (UTC). strtotime() interprets a
        // naked date string in the PHP default timezone, which causes drift on
        // servers not running UTC. Use DateTime with an explicit UTC tz.
        try {
            $expiresAt = new \DateTime($token->getExpiresAt(), new \DateTimeZone('UTC'));
        } catch (\Throwable) {
            return true;
        }
        return ($expiresAt->getTimestamp() - time()) <= self::REFRESH_SKEW_SECONDS;
    }

    /**
     * Calls the OAuth endpoint and stores the resulting token in the cache table.
     *
     * @throws ApiException
     */
    private function issueAndPersist(int $storeId, string $scope, ?TokenInterface $existing): TokenInterface
    {
        $apiUrl = $this->helper->getApiUrl($storeId);
        $clientId = $this->helper->getClientId($storeId);
        $clientSecret = $this->helper->getClientSecret($storeId);

        if ($apiUrl === '' || $clientId === '' || $clientSecret === '') {
            throw new ApiException(
                __('99Entrega API credentials are not configured.')
            );
        }

        $this->helper->logDebug('OAuth issuing token', [
            'url'              => $apiUrl . self::TOKEN_PATH,
            'client_id_len'    => strlen($clientId),
            'client_secret_len'=> strlen($clientSecret),
            'scope'            => $scope,
        ]);

        $http = $this->httpClientFactory->create([
            'config' => [
                'base_uri'    => $apiUrl . '/',
                'http_errors' => false,
                'timeout'     => 15,
            ],
        ]);

        try {
            $response = $http->post(ltrim(self::TOKEN_PATH, '/'), [
                'headers'     => ['Content-Type' => 'application/x-www-form-urlencoded'],
                'form_params' => [
                    'client_id'     => $clientId,
                    'client_secret' => $clientSecret,
                    'grant_type'    => 'client_credentials',
                    'scope'         => $scope,
                ],
            ]);
        } catch (GuzzleException $e) {
            $this->helper->logException($e, 'OAuth request failed');
            throw new ApiException(__('99Entrega OAuth request failed: %1', $e->getMessage()), $e);
        }

        $status = $response->getStatusCode();
        $body = (string)$response->getBody();
        $decoded = json_decode($body, true);

        $this->helper->logDebug('OAuth response', ['status' => $status, 'body' => $this->sanitizeOAuthBody($body)]);

        if (!is_array($decoded) || ($decoded['errno'] ?? null) !== 0 || !isset($decoded['data']['access_token'])) {
            $errno = (int)($decoded['errno'] ?? $status);
            $errmsg = (string)($decoded['errmsg'] ?? 'unknown error');
            throw new ApiException(
                __('99Entrega OAuth failed (errno=%1): %2', $errno, $errmsg),
                null,
                $errno,
                is_array($decoded) ? $decoded : null
            );
        }

        $data = $decoded['data'];
        $expiresIn = (int)($data['expires_in'] ?? 7200);
        $expiresAt = gmdate('Y-m-d H:i:s', time() + $expiresIn);

        $token = $existing ?: $this->tokenFactory->create();
        $token->setStoreId($storeId)
            ->setScope($scope)
            ->setAccessToken((string)$data['access_token'])
            ->setTokenType((string)($data['token_type'] ?? 'Bearer'))
            ->setExpiresAt($expiresAt);

        $this->tokenRepository->save($token);
        $this->helper->logInfo('99Entrega token refreshed', ['store_id' => $storeId, 'scope' => $scope, 'expires_at' => $expiresAt]);

        return $token;
    }

    /**
     * Replaces the access_token value in the OAuth response body with `***`
     * so debug logs don't leak live bearer tokens.
     */
    private function sanitizeOAuthBody(string $body): string
    {
        return (string)preg_replace(
            '/"access_token"\s*:\s*"[^"]*"/',
            '"access_token":"***"',
            $body
        );
    }
}
