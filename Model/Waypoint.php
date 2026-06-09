<?php
/**
 * MageShop | Entrega99
 *
 * @category MageShop
 * @package  Entrega99
 */

declare(strict_types=1);

namespace MageShop\Entrega99\Model;

use MageShop\Entrega99\Api\Data\WaypointInterface;
use MageShop\Entrega99\Model\ResourceModel\Waypoint as WaypointResource;
use Magento\Framework\Model\AbstractModel;

class Waypoint extends AbstractModel implements WaypointInterface
{
    protected $_eventPrefix = 'mageshop_entrega99_waypoint';
    protected $_idFieldName = 'waypoint_id';

    protected function _construct(): void
    {
        $this->_init(WaypointResource::class);
    }

    public function getWaypointId(): ?int
    {
        $value = $this->getData(self::WAYPOINT_ID);
        return $value === null ? null : (int)$value;
    }

    public function setWaypointId(int $waypointId): self
    {
        return $this->setData(self::WAYPOINT_ID, $waypointId);
    }

    public function getId()
    {
        return $this->getWaypointId();
    }

    public function getStoreId(): ?int
    {
        $value = $this->getData(self::STORE_ID);
        return $value === null ? null : (int)$value;
    }

    public function setStoreId(?int $storeId): self
    {
        return $this->setData(self::STORE_ID, $storeId);
    }

    public function getMsiSourceCode(): ?string
    {
        $value = $this->getData(self::MSI_SOURCE_CODE);
        return $value === null ? null : (string)$value;
    }

    public function setMsiSourceCode(?string $sourceCode): self
    {
        return $this->setData(self::MSI_SOURCE_CODE, $sourceCode);
    }

    public function getName(): string
    {
        return (string)$this->getData(self::NAME);
    }

    public function setName(string $name): self
    {
        return $this->setData(self::NAME, $name);
    }

    public function getActive(): bool
    {
        return (bool)$this->getData(self::ACTIVE);
    }

    public function setActive(bool $active): self
    {
        return $this->setData(self::ACTIVE, $active);
    }

    public function getContactName(): ?string
    {
        $value = $this->getData(self::CONTACT_NAME);
        return $value === null ? null : (string)$value;
    }

    public function setContactName(?string $contactName): self
    {
        return $this->setData(self::CONTACT_NAME, $contactName);
    }

    public function getTelephone(): ?string
    {
        $value = $this->getData(self::TELEPHONE);
        return $value === null ? null : (string)$value;
    }

    public function setTelephone(?string $telephone): self
    {
        return $this->setData(self::TELEPHONE, $telephone);
    }

    public function getEmail(): ?string
    {
        $value = $this->getData(self::EMAIL);
        return $value === null ? null : (string)$value;
    }

    public function setEmail(?string $email): self
    {
        return $this->setData(self::EMAIL, $email);
    }

    public function getAddress(): string
    {
        return (string)$this->getData(self::ADDRESS);
    }

    public function setAddress(string $address): self
    {
        return $this->setData(self::ADDRESS, $address);
    }

    public function getNumber(): ?string
    {
        $value = $this->getData(self::NUMBER);
        return $value === null ? null : (string)$value;
    }

    public function setNumber(?string $number): self
    {
        return $this->setData(self::NUMBER, $number);
    }

    public function getComplement(): ?string
    {
        $value = $this->getData(self::COMPLEMENT);
        return $value === null ? null : (string)$value;
    }

    public function setComplement(?string $complement): self
    {
        return $this->setData(self::COMPLEMENT, $complement);
    }

    public function getNeighborhood(): ?string
    {
        $value = $this->getData(self::NEIGHBORHOOD);
        return $value === null ? null : (string)$value;
    }

    public function setNeighborhood(?string $neighborhood): self
    {
        return $this->setData(self::NEIGHBORHOOD, $neighborhood);
    }

    public function getCity(): string
    {
        return (string)$this->getData(self::CITY);
    }

    public function setCity(string $city): self
    {
        return $this->setData(self::CITY, $city);
    }

    public function getRegion(): ?string
    {
        $value = $this->getData(self::REGION);
        return $value === null ? null : (string)$value;
    }

    public function setRegion(?string $region): self
    {
        return $this->setData(self::REGION, $region);
    }

    public function getPostcode(): string
    {
        return (string)$this->getData(self::POSTCODE);
    }

    public function setPostcode(string $postcode): self
    {
        return $this->setData(self::POSTCODE, $postcode);
    }

    public function getCountry(): string
    {
        return (string)($this->getData(self::COUNTRY) ?: 'BR');
    }

    public function setCountry(string $country): self
    {
        return $this->setData(self::COUNTRY, $country);
    }

    public function getLatitude(): ?float
    {
        $value = $this->getData(self::LATITUDE);
        return $value === null || $value === '' ? null : (float)$value;
    }

    public function setLatitude(?float $latitude): self
    {
        return $this->setData(self::LATITUDE, $latitude);
    }

    public function getLongitude(): ?float
    {
        $value = $this->getData(self::LONGITUDE);
        return $value === null || $value === '' ? null : (float)$value;
    }

    public function setLongitude(?float $longitude): self
    {
        return $this->setData(self::LONGITUDE, $longitude);
    }

    public function getInstructions(): ?string
    {
        $value = $this->getData(self::INSTRUCTIONS);
        return $value === null ? null : (string)$value;
    }

    public function setInstructions(?string $instructions): self
    {
        return $this->setData(self::INSTRUCTIONS, $instructions);
    }

    public function getCreatedAt(): ?string
    {
        $value = $this->getData(self::CREATED_AT);
        return $value === null ? null : (string)$value;
    }

    public function setCreatedAt(string $createdAt): self
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    public function getUpdatedAt(): ?string
    {
        $value = $this->getData(self::UPDATED_AT);
        return $value === null ? null : (string)$value;
    }

    public function setUpdatedAt(string $updatedAt): self
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }

    public function getOperatingHours(): array
    {
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $result = [];
        foreach ($days as $day) {
            $open = $this->getData($day . '_open');
            $close = $this->getData($day . '_close');
            $result[$day] = [
                'open'  => $open !== null && $open !== '' ? (string)$open : null,
                'close' => $close !== null && $close !== '' ? (string)$close : null,
            ];
        }
        return $result;
    }
}
