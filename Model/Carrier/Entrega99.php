<?php
/**
 * MageShop | Entrega99
 *
 * @category MageShop
 * @package  Entrega99
 */

declare(strict_types=1);

namespace MageShop\Entrega99\Model\Carrier;

use MageShop\Entrega99\Api\Data\WaypointInterface;
use MageShop\Entrega99\Api\GeocoderInterface;
use MageShop\Entrega99\Api\WaypointRepositoryInterface;
use MageShop\Entrega99\Exception\ApiException;
use MageShop\Entrega99\Helper\Data as Helper;
use MageShop\Entrega99\Model\Api\Client;
use MageShop\Entrega99\Model\ApiErrorTranslator;
use MageShop\Entrega99\Model\CepLookup;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Module\Dir\Reader as ModuleReader;
use Magento\Framework\Xml\Security;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Shipping\Model\Carrier\AbstractCarrierOnline;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Rate\Result;
use Magento\Shipping\Model\Rate\ResultFactory;
use Magento\Shipping\Model\Simplexml\ElementFactory;
use Magento\Shipping\Model\Tracking\Result\ErrorFactory as TrackingErrorFactory;
use Magento\Shipping\Model\Tracking\Result\StatusFactory;
use Magento\Shipping\Model\Tracking\ResultFactory as TrackingResultFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * 99Entrega shipping carrier.
 *
 * collectRates() flow:
 *   1. Check carrier is active and credentials are present
 *   2. Validate destination (postcode + street)
 *   3. Resolve a pickup waypoint for the current store
 *   4. Geocode the customer dropoff address → lat/lng
 *   5. Call /v2/order/estimate with vehicle_type + pickup + dropoff
 *   6. Build a single rate Method with the returned fee (cents → currency)
 *   7. Cache the estimate_id on the checkout session for CreateShipment
 *
 * On ANY failure the carrier returns false (hides itself) instead of breaking checkout.
 */
class Entrega99 extends AbstractCarrierOnline implements CarrierInterface
{
    public const CARRIER_CODE = 'entrega99';
    public const METHOD_CODE  = 'delivery';

    /** @var string */
    protected $_code = self::CARRIER_CODE;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ErrorFactory $rateErrorFactory,
        LoggerInterface $logger,
        Security $xmlSecurity,
        ElementFactory $xmlElFactory,
        ResultFactory $rateFactory,
        MethodFactory $rateMethodFactory,
        TrackingResultFactory $trackFactory,
        TrackingErrorFactory $trackErrorFactory,
        StatusFactory $trackStatusFactory,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \Magento\Directory\Model\CurrencyFactory $currencyFactory,
        DirectoryHelper $directoryData,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        private readonly Helper $helperEntrega99,
        private readonly Client $apiClient,
        private readonly GeocoderInterface $geocoder,
        private readonly WaypointRepositoryInterface $waypointRepository,
        private readonly CheckoutSession $checkoutSession,
        private readonly StoreManagerInterface $storeManager,
        private readonly CepLookup $cepLookup,
        private readonly ApiErrorTranslator $errorTranslator,
        array $data = []
    ) {
        parent::__construct(
            $scopeConfig,
            $rateErrorFactory,
            $logger,
            $xmlSecurity,
            $xmlElFactory,
            $rateFactory,
            $rateMethodFactory,
            $trackFactory,
            $trackErrorFactory,
            $trackStatusFactory,
            $regionFactory,
            $countryFactory,
            $currencyFactory,
            $directoryData,
            $stockRegistry,
            $data
        );
    }

    /**
     * @return Result|bool
     */
    public function collectRates(RateRequest $request)
    {
        $this->helperEntrega99->logInfo('collectRates: entry', [
            'dest_country' => $request->getDestCountryId(),
            'dest_region'  => $request->getDestRegionId(),
            'dest_city'    => $request->getDestCity(),
            'dest_postcode'=> $request->getDestPostcode(),
            'free_ship'    => $request->getFreeShipping(),
            'package_qty'  => $request->getPackageQty(),
        ]);

        if (!$this->getConfigFlag('active')) {
            return false;
        }

        $storeId = (int)($request->getStoreId() ?? $this->storeManager->getStore()->getId());

        try {
            $this->validateDestination($request);

            $waypoint = $this->resolveWaypoint($storeId);
            if ($waypoint === null) {
                throw new LocalizedException(__('No active 99Entrega pickup location configured for this store.'));
            }
            if ($waypoint->getLatitude() === null || $waypoint->getLongitude() === null) {
                throw new LocalizedException(__('Pickup location is missing latitude/longitude.'));
            }
            $this->helperEntrega99->logDebug('collectRates: waypoint resolved', [
                'waypoint_id' => $waypoint->getWaypointId(),
                'name'        => $waypoint->getName(),
            ]);

            $dropoff = $this->resolveDropoffCoordinates($request, $storeId);
            if ($dropoff === null) {
                throw new LocalizedException(__('Could not geocode the delivery address.'));
            }
            $this->helperEntrega99->logDebug('collectRates: dropoff coordinates', $dropoff);

            $vehicleType = $this->helperEntrega99->getVehicleType($storeId);

            $estimate = $this->apiClient->post('/v2/order/estimate', [
                'vehicle_type' => $vehicleType,
                'pickup_info'  => [
                    'address'   => $this->formatWaypointAddress($waypoint),
                    'latitude'  => (float)$waypoint->getLatitude(),
                    'longitude' => (float)$waypoint->getLongitude(),
                ],
                'dropoff_info' => [
                    'address'   => $this->formatDropoffAddress($request),
                    'latitude'  => $dropoff['latitude'],
                    'longitude' => $dropoff['longitude'],
                ],
            ], $storeId);

            if (empty($estimate['id']) || !isset($estimate['fee'])) {
                throw new LocalizedException(__('99Entrega did not return a valid estimate.'));
            }

            $feeInCents = (int)$estimate['fee'];
            $priceMoney = $feeInCents / 100;
            $isFreeShipping = $this->getConfigFlag('free_shipping') && $request->getFreeShipping();

            // Cache estimate on session for CreateShipment to honor (or fall back to re-quote)
            $this->checkoutSession->setEntrega99EstimateId((string)$estimate['id']);
            $this->checkoutSession->setEntrega99EstimateExpiresAt((int)($estimate['expires_time'] ?? 0));
            $this->checkoutSession->setEntrega99WaypointId((int)$waypoint->getWaypointId());
            $this->checkoutSession->setEntrega99VehicleType($vehicleType);
            $this->checkoutSession->setEntrega99FeeCents($feeInCents);
            $this->checkoutSession->setEntrega99Currency((string)($estimate['currency'] ?? 'R$'));

            /** @var Result $result */
            $result = $this->_rateFactory->create();
            $method = $this->_rateMethodFactory->create();
            $method->setCarrier(self::CARRIER_CODE);
            $method->setCarrierTitle($this->getConfigData('title'));
            $method->setMethod(self::METHOD_CODE);
            $method->setMethodTitle($this->buildMethodTitle($estimate, $vehicleType, $storeId));
            $method->setPrice($isFreeShipping ? 0.0 : $priceMoney);
            $method->setCost($priceMoney);

            $result->append($method);

            $this->helperEntrega99->logInfo('collectRates: SUCCESS — rate appended', [
                'fee_cents'  => $feeInCents,
                'estimate_id'=> $estimate['id'] ?? null,
                'vehicle'    => $vehicleType,
                'store_id'   => $storeId,
            ]);

            return $result;
        } catch (ApiException $e) {
            $this->helperEntrega99->logException($e, 'collectRates ApiException');
            // Customer-facing message — translate 99 errno to friendly text.
            // Technical detail (errno + raw errmsg) is in the log above.
            $userMessage = (string)$this->errorTranslator->translate($e->getErrno());
            return $this->errorResult($userMessage);
        } catch (LocalizedException $e) {
            $this->helperEntrega99->logInfo('collectRates: skipped via LocalizedException', ['reason' => $e->getMessage()]);
            return $this->errorResult((string)$e->getMessage());
        } catch (\Throwable $e) {
            $this->helperEntrega99->logException($e, 'collectRates unexpected error');
            return $this->errorResult((string)$this->errorTranslator->getGenericMessage());
        }
    }

    public function getAllowedMethods(): array
    {
        return [self::METHOD_CODE => (string)$this->getConfigData('title')];
    }

    /**
     * Bypass parent's AbstractCarrierOnline::processAdditionalValidation, which
     * rejects every item with positive weight when max_package_weight is not
     * configured (default 0). We do our own validation inside collectRates.
     */
    public function processAdditionalValidation(\Magento\Framework\DataObject $request)
    {
        return $this;
    }

    /**
     * Real-time tracking is not implemented in this MVP. Tracking URL is shown
     * on the order page instead.
     */
    protected function _doShipmentRequest(\Magento\Framework\DataObject $request): \Magento\Framework\DataObject
    {
        return new \Magento\Framework\DataObject();
    }

    // ============== Internals ==============

    private function validateDestination(RateRequest $request): void
    {
        // Postcode is the minimum we need. Street/city may be empty at the
        // mini-cart "Estimate Shipping" step — geocoder will resolve from CEP
        // centroid. The full address (re-quoted) is used at CreateShipment time.
        $postcode = trim((string)($request->getDestPostcode() ?? ''));
        if ($postcode === '') {
            throw new LocalizedException(__('Postcode is required for 99Entrega quote.'));
        }
    }

    private function resolveWaypoint(int $storeId): ?WaypointInterface
    {
        return $this->waypointRepository->getFirstActive($storeId);
    }

    /**
     * @return array{latitude: float, longitude: float}|null
     */
    private function resolveDropoffCoordinates(RateRequest $request, int $storeId): ?array
    {
        $regionName = '';
        try {
            $regionId = (int)$request->getDestRegionId();
            if ($regionId > 0) {
                $region = $this->_regionFactory->create()->load($regionId);
                $regionName = (string)$region->getName();
            }
        } catch (\Throwable $e) {
            $this->helperEntrega99->logException($e, 'Failed to load region');
        }

        $streetLines = explode("\n", (string)($request->getDestStreet() ?? ''));
        $street = $streetLines[0] ?? '';
        $number = $streetLines[1] ?? '';

        $parts = [
            'street'       => trim((string)$street),
            'number'       => trim((string)$number),
            'neighborhood' => '',
            'city'         => trim((string)($request->getDestCity() ?? '')),
            'region'       => trim((string)$regionName),
            'postcode'     => trim((string)($request->getDestPostcode() ?? '')),
            'country'      => strtoupper(trim((string)($request->getDestCountryId() ?? 'BR'))),
        ];

        $result = $this->geocoder->geocode($parts, $storeId);
        if ($result === null) {
            return null;
        }
        return ['latitude' => $result->latitude, 'longitude' => $result->longitude];
    }

    private function formatWaypointAddress(WaypointInterface $waypoint): string
    {
        $components = array_filter([
            trim($waypoint->getAddress()),
            $waypoint->getNumber() !== null ? trim((string)$waypoint->getNumber()) : null,
            $waypoint->getNeighborhood() !== null ? trim((string)$waypoint->getNeighborhood()) : null,
            trim($waypoint->getCity()),
            $waypoint->getRegion() !== null ? trim((string)$waypoint->getRegion()) : null,
            trim($waypoint->getPostcode()),
            $waypoint->getCountry(),
        ]);
        return implode(', ', $components);
    }

    private function formatDropoffAddress(RateRequest $request): string
    {
        $streetLines = explode("\n", (string)($request->getDestStreet() ?? ''));
        $components = array_filter([
            trim((string)($streetLines[0] ?? '')),
            isset($streetLines[1]) ? trim((string)$streetLines[1]) : null,
            trim((string)($request->getDestCity() ?? '')),
            trim((string)($request->getDestPostcode() ?? '')),
            strtoupper(trim((string)($request->getDestCountryId() ?? 'BR'))),
        ]);
        return implode(', ', $components);
    }

    private function buildMethodTitle(array $estimate, string $vehicleType, int $storeId): string
    {
        $description = (string)$this->getConfigData('description');
        if ($description === '') {
            $description = $vehicleType === 'entrega_moto' ? 'Motoboy' : 'Carro';
        }

        $durationMinutes = (int)($estimate['delivery_duration'] ?? 0);
        $promiseTime = $this->helperEntrega99->getPromiseTime($storeId);
        $eta = $durationMinutes + $promiseTime;

        if ($eta > 0) {
            return sprintf('%s (~%d min)', $description, $eta);
        }
        return $description;
    }

    /**
     * Builds an empty result. If `showmethod` is enabled, attaches an Error so
     * the customer sees why the method is unavailable. Otherwise silently hides.
     *
     * @return Result|false
     */
    private function errorResult(string $message)
    {
        if (!$this->getConfigFlag('showmethod')) {
            return false;
        }
        /** @var Result $result */
        $result = $this->_rateFactory->create();
        $error = $this->_rateErrorFactory->create();
        $error->setCarrier(self::CARRIER_CODE);
        $error->setCarrierTitle($this->getConfigData('title'));
        $error->setErrorMessage($message !== '' ? $message : (string)$this->getConfigData('specificerrmsg'));
        $result->append($error);
        return $result;
    }
}
