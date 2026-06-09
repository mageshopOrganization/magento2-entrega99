<?php
/**
 * MageShop | Entrega99
 *
 * @category MageShop
 * @package  Entrega99
 */

declare(strict_types=1);

namespace MageShop\Entrega99\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Central config reader + log facade for the 99Entrega module.
 *
 * All configuration lives under `carriers/entrega99/...` so the standard
 * Sales → Shipping Methods admin screen renders our group.
 */
class Data
{
    public const XML_PATH_PREFIX = 'carriers/entrega99/';

    // General
    public const XML_PATH_ACTIVE        = self::XML_PATH_PREFIX . 'active';
    public const XML_PATH_TITLE         = self::XML_PATH_PREFIX . 'title';
    public const XML_PATH_DESCRIPTION   = self::XML_PATH_PREFIX . 'description';
    public const XML_PATH_ENVIRONMENT   = self::XML_PATH_PREFIX . 'environment';

    // API
    public const XML_PATH_API_URL       = self::XML_PATH_PREFIX . 'api_url';
    public const XML_PATH_CLIENT_ID     = self::XML_PATH_PREFIX . 'client_id';
    public const XML_PATH_CLIENT_SECRET = self::XML_PATH_PREFIX . 'client_secret';
    public const XML_PATH_TOKEN_SCOPE   = self::XML_PATH_PREFIX . 'token_scope';

    // Webhook
    public const XML_PATH_WEBHOOK_SIGNING_KEY = self::XML_PATH_PREFIX . 'webhook_signing_key';

    // Delivery options
    public const XML_PATH_VEHICLE_TYPE         = self::XML_PATH_PREFIX . 'vehicle_type';
    public const XML_PATH_NEED_PICKUP_CODE     = self::XML_PATH_PREFIX . 'need_pickup_code';
    public const XML_PATH_NEED_DROPOFF_CODE    = self::XML_PATH_PREFIX . 'need_dropoff_code';
    public const XML_PATH_RETURN_HANDOVER      = self::XML_PATH_PREFIX . 'return_handover_method';
    public const XML_PATH_AUTOMATIC_SHIPMENT   = self::XML_PATH_PREFIX . 'automatic_shipment';
    public const XML_PATH_FREE_SHIPPING        = self::XML_PATH_PREFIX . 'free_shipping';
    public const XML_PATH_PROMISE_TIME         = self::XML_PATH_PREFIX . 'promise_time';

    // Geocoder
    public const XML_PATH_GEOCODER_PROVIDER    = self::XML_PATH_PREFIX . 'geocoder_provider';
    public const XML_PATH_GOOGLE_MAPS_KEY      = self::XML_PATH_PREFIX . 'google_maps_api_key';
    public const XML_PATH_NOMINATIM_UA         = self::XML_PATH_PREFIX . 'nominatim_user_agent';

    // Advanced
    public const XML_PATH_DEBUG_LOG = self::XML_PATH_PREFIX . 'debug_log';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly EncryptorInterface $encryptor,
        private readonly StoreManagerInterface $storeManager,
        private readonly LoggerInterface $logger
    ) {
    }

    // ============== General ==============

    public function isEnabled(?int $storeId = null): bool
    {
        return $this->getFlag(self::XML_PATH_ACTIVE, $storeId);
    }

    public function getTitle(?int $storeId = null): string
    {
        return (string)$this->getValue(self::XML_PATH_TITLE, $storeId);
    }

    public function getDescription(?int $storeId = null): string
    {
        return (string)$this->getValue(self::XML_PATH_DESCRIPTION, $storeId);
    }

    public function getEnvironment(?int $storeId = null): string
    {
        return (string)($this->getValue(self::XML_PATH_ENVIRONMENT, $storeId) ?: 'sandbox');
    }

    public function isProduction(?int $storeId = null): bool
    {
        return $this->getEnvironment($storeId) === 'production';
    }

    // ============== API ==============

    public function getApiUrl(?int $storeId = null): string
    {
        return rtrim((string)$this->getValue(self::XML_PATH_API_URL, $storeId), '/');
    }

    public function getClientId(?int $storeId = null): string
    {
        return $this->getDecrypted(self::XML_PATH_CLIENT_ID, $storeId);
    }

    public function getClientSecret(?int $storeId = null): string
    {
        return $this->getDecrypted(self::XML_PATH_CLIENT_SECRET, $storeId);
    }

    public function getTokenScope(?int $storeId = null): string
    {
        return (string)($this->getValue(self::XML_PATH_TOKEN_SCOPE, $storeId) ?: 'entrega.order');
    }

    // ============== Webhook ==============

    public function getWebhookSigningKey(?int $storeId = null): string
    {
        return $this->getDecrypted(self::XML_PATH_WEBHOOK_SIGNING_KEY, $storeId);
    }

    // ============== Delivery ==============

    public function getVehicleType(?int $storeId = null): string
    {
        return (string)($this->getValue(self::XML_PATH_VEHICLE_TYPE, $storeId) ?: 'entrega_moto');
    }

    public function needPickupCode(?int $storeId = null): bool
    {
        return $this->getFlag(self::XML_PATH_NEED_PICKUP_CODE, $storeId);
    }

    public function needDropoffCode(?int $storeId = null): bool
    {
        return $this->getFlag(self::XML_PATH_NEED_DROPOFF_CODE, $storeId);
    }

    public function getReturnHandoverMethod(?int $storeId = null): int
    {
        return (int)($this->getValue(self::XML_PATH_RETURN_HANDOVER, $storeId) ?: 1);
    }

    public function isAutomaticShipment(?int $storeId = null): bool
    {
        return $this->getFlag(self::XML_PATH_AUTOMATIC_SHIPMENT, $storeId);
    }

    public function isFreeShipping(?int $storeId = null): bool
    {
        return $this->getFlag(self::XML_PATH_FREE_SHIPPING, $storeId);
    }

    public function getPromiseTime(?int $storeId = null): int
    {
        return (int)($this->getValue(self::XML_PATH_PROMISE_TIME, $storeId) ?: 20);
    }

    // ============== Geocoder ==============

    public function getGeocoderProvider(?int $storeId = null): string
    {
        return (string)($this->getValue(self::XML_PATH_GEOCODER_PROVIDER, $storeId) ?: 'nominatim');
    }

    public function getGoogleMapsApiKey(?int $storeId = null): string
    {
        return $this->getDecrypted(self::XML_PATH_GOOGLE_MAPS_KEY, $storeId);
    }

    public function getNominatimUserAgent(?int $storeId = null): string
    {
        return (string)($this->getValue(self::XML_PATH_NOMINATIM_UA, $storeId) ?: 'MageShop-Entrega99/1.0');
    }

    // ============== Misc store config ==============

    /**
     * Returns 'kgs' or 'lbs' (Magento native config).
     */
    public function getStoreWeightUnit(?int $storeId = null): string
    {
        $value = (string)$this->getValue('general/locale/weight_unit', $storeId);
        return $value !== '' ? $value : 'kgs';
    }

    // ============== Logging ==============

    public function isDebugLogEnabled(?int $storeId = null): bool
    {
        return $this->getFlag(self::XML_PATH_DEBUG_LOG, $storeId);
    }

    /**
     * Verbose log — only writes if debug_log is enabled.
     */
    public function logDebug(string $message, array $context = []): void
    {
        if ($this->isDebugLogEnabled()) {
            $this->logger->debug($message, $context);
        }
    }

    /**
     * Info — always written (lightweight events worth recording).
     */
    public function logInfo(string $message, array $context = []): void
    {
        $this->logger->info($message, $context);
    }

    /**
     * Error — always written.
     */
    public function logError(string $message, array $context = []): void
    {
        $this->logger->error($message, $context);
    }

    public function logException(\Throwable $e, ?string $contextMessage = null): void
    {
        $this->logger->error(
            ($contextMessage ? $contextMessage . ': ' : '') . $e->getMessage(),
            ['exception' => $e]
        );
    }

    // ============== Internals ==============

    private function getValue(string $path, ?int $storeId = null): mixed
    {
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
    }

    private function getFlag(string $path, ?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag($path, ScopeInterface::SCOPE_STORE, $storeId);
    }

    private function getDecrypted(string $path, ?int $storeId = null): string
    {
        $value = (string)$this->getValue($path, $storeId);
        if ($value === '') {
            return '';
        }

        // Magento's encrypted format always starts with version markers like `0:3:`
        // or `1:1:`. If the stored value doesn't match that, it was saved as plain
        // text (e.g. via CLI `config:set` or a migration that bypassed the
        // Encrypted backend) — return as-is to avoid running Blowfish decrypt on
        // a non-encrypted string, which returns binary garbage.
        if (!preg_match('/^\d+:\d+:/', $value)) {
            return $value;
        }

        try {
            return (string)$this->encryptor->decrypt($value);
        } catch (\Throwable $e) {
            $this->logException($e, 'Failed to decrypt config ' . $path);
            return '';
        }
    }

    /**
     * Resolves a store id from context when none is given (used by callers
     * that don't track which store they're in, e.g. the webhook).
     */
    public function resolveStoreId(?int $storeId = null): int
    {
        if ($storeId !== null) {
            return $storeId;
        }
        try {
            return (int)$this->storeManager->getStore()->getId();
        } catch (\Throwable) {
            return 0;
        }
    }
}
