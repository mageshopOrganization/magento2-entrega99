<?php
/**
 * MageShop | Entrega99
 *
 * @category MageShop
 * @package  Entrega99
 */

declare(strict_types=1);

namespace MageShop\Entrega99\Api\Data;

interface TokenInterface
{
    public const ENTITY_ID    = 'entity_id';
    public const STORE_ID     = 'store_id';
    public const ACCESS_TOKEN = 'access_token';
    public const TOKEN_TYPE   = 'token_type';
    public const SCOPE        = 'scope';
    public const EXPIRES_AT   = 'expires_at';
    public const CREATED_AT   = 'created_at';

    public function getEntityId(): ?int;
    public function setEntityId(int $entityId): self;

    public function getStoreId(): int;
    public function setStoreId(int $storeId): self;

    public function getAccessToken(): string;
    public function setAccessToken(string $accessToken): self;

    public function getTokenType(): string;
    public function setTokenType(string $tokenType): self;

    public function getScope(): string;
    public function setScope(string $scope): self;

    public function getExpiresAt(): string;
    public function setExpiresAt(string $expiresAt): self;

    public function getCreatedAt(): ?string;
    public function setCreatedAt(string $createdAt): self;
}
