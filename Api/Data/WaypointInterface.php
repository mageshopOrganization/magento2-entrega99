<?php
/**
 * MageShop | Entrega99
 *
 * @category MageShop
 * @package  Entrega99
 */

declare(strict_types=1);

namespace MageShop\Entrega99\Api\Data;

interface WaypointInterface
{
    public const WAYPOINT_ID      = 'waypoint_id';
    public const STORE_ID         = 'store_id';
    public const MSI_SOURCE_CODE  = 'msi_source_code';
    public const NAME             = 'name';
    public const ACTIVE           = 'active';
    public const CONTACT_NAME     = 'contact_name';
    public const TELEPHONE        = 'telephone';
    public const EMAIL            = 'email';
    public const ADDRESS          = 'address';
    public const NUMBER           = 'number';
    public const COMPLEMENT       = 'complement';
    public const NEIGHBORHOOD     = 'neighborhood';
    public const CITY             = 'city';
    public const REGION           = 'region';
    public const POSTCODE         = 'postcode';
    public const COUNTRY          = 'country';
    public const LATITUDE         = 'latitude';
    public const LONGITUDE        = 'longitude';
    public const INSTRUCTIONS     = 'instructions';
    public const MONDAY_OPEN      = 'monday_open';
    public const MONDAY_CLOSE     = 'monday_close';
    public const TUESDAY_OPEN     = 'tuesday_open';
    public const TUESDAY_CLOSE    = 'tuesday_close';
    public const WEDNESDAY_OPEN   = 'wednesday_open';
    public const WEDNESDAY_CLOSE  = 'wednesday_close';
    public const THURSDAY_OPEN    = 'thursday_open';
    public const THURSDAY_CLOSE   = 'thursday_close';
    public const FRIDAY_OPEN      = 'friday_open';
    public const FRIDAY_CLOSE     = 'friday_close';
    public const SATURDAY_OPEN    = 'saturday_open';
    public const SATURDAY_CLOSE   = 'saturday_close';
    public const SUNDAY_OPEN      = 'sunday_open';
    public const SUNDAY_CLOSE     = 'sunday_close';
    public const CREATED_AT       = 'created_at';
    public const UPDATED_AT       = 'updated_at';

    public function getWaypointId(): ?int;
    public function setWaypointId(int $waypointId): self;

    public function getStoreId(): ?int;
    public function setStoreId(?int $storeId): self;

    public function getMsiSourceCode(): ?string;
    public function setMsiSourceCode(?string $sourceCode): self;

    public function getName(): string;
    public function setName(string $name): self;

    public function getActive(): bool;
    public function setActive(bool $active): self;

    public function getContactName(): ?string;
    public function setContactName(?string $contactName): self;

    public function getTelephone(): ?string;
    public function setTelephone(?string $telephone): self;

    public function getEmail(): ?string;
    public function setEmail(?string $email): self;

    public function getAddress(): string;
    public function setAddress(string $address): self;

    public function getNumber(): ?string;
    public function setNumber(?string $number): self;

    public function getComplement(): ?string;
    public function setComplement(?string $complement): self;

    public function getNeighborhood(): ?string;
    public function setNeighborhood(?string $neighborhood): self;

    public function getCity(): string;
    public function setCity(string $city): self;

    public function getRegion(): ?string;
    public function setRegion(?string $region): self;

    public function getPostcode(): string;
    public function setPostcode(string $postcode): self;

    public function getCountry(): string;
    public function setCountry(string $country): self;

    public function getLatitude(): ?float;
    public function setLatitude(?float $latitude): self;

    public function getLongitude(): ?float;
    public function setLongitude(?float $longitude): self;

    public function getInstructions(): ?string;
    public function setInstructions(?string $instructions): self;

    public function getCreatedAt(): ?string;
    public function setCreatedAt(string $createdAt): self;

    public function getUpdatedAt(): ?string;
    public function setUpdatedAt(string $updatedAt): self;

    /**
     * @return array<string, array{open: ?string, close: ?string}>
     *   keys: monday..sunday
     */
    public function getOperatingHours(): array;
}
