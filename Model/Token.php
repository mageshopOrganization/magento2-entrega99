<?php
/**
 * MageShop | Entrega99
 *
 * @category MageShop
 * @package  Entrega99
 */

declare(strict_types=1);

namespace MageShop\Entrega99\Model;

use MageShop\Entrega99\Api\Data\TokenInterface;
use MageShop\Entrega99\Model\ResourceModel\Token as TokenResource;
use Magento\Framework\Model\AbstractModel;

class Token extends AbstractModel implements TokenInterface
{
    protected $_eventPrefix = 'mageshop_entrega99_token';

    protected function _construct(): void
    {
        $this->_init(TokenResource::class);
    }

    public function getEntityId(): ?int
    {
        $value = $this->getData(self::ENTITY_ID);
        return $value === null ? null : (int)$value;
    }

    public function setEntityId($entityId): self
    {
        return $this->setData(self::ENTITY_ID, (int)$entityId);
    }

    public function getStoreId(): int
    {
        return (int)$this->getData(self::STORE_ID);
    }

    public function setStoreId(int $storeId): self
    {
        return $this->setData(self::STORE_ID, $storeId);
    }

    public function getAccessToken(): string
    {
        return (string)$this->getData(self::ACCESS_TOKEN);
    }

    public function setAccessToken(string $accessToken): self
    {
        return $this->setData(self::ACCESS_TOKEN, $accessToken);
    }

    public function getTokenType(): string
    {
        return (string)$this->getData(self::TOKEN_TYPE);
    }

    public function setTokenType(string $tokenType): self
    {
        return $this->setData(self::TOKEN_TYPE, $tokenType);
    }

    public function getScope(): string
    {
        return (string)$this->getData(self::SCOPE);
    }

    public function setScope(string $scope): self
    {
        return $this->setData(self::SCOPE, $scope);
    }

    public function getExpiresAt(): string
    {
        return (string)$this->getData(self::EXPIRES_AT);
    }

    public function setExpiresAt(string $expiresAt): self
    {
        return $this->setData(self::EXPIRES_AT, $expiresAt);
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
}
