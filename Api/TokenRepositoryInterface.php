<?php
/**
 * MageShop | Entrega99
 *
 * @category MageShop
 * @package  Entrega99
 */

declare(strict_types=1);

namespace MageShop\Entrega99\Api;

use MageShop\Entrega99\Api\Data\TokenInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

interface TokenRepositoryInterface
{
    /**
     * @throws CouldNotSaveException
     */
    public function save(TokenInterface $token): TokenInterface;

    /**
     * @throws NoSuchEntityException
     */
    public function getById(int $entityId): TokenInterface;

    /**
     * Returns the cached token for the given store/scope, or null if none.
     */
    public function findByStoreAndScope(int $storeId, string $scope): ?TokenInterface;

    /**
     * @throws CouldNotDeleteException
     */
    public function delete(TokenInterface $token): bool;

    /**
     * @throws NoSuchEntityException
     * @throws CouldNotDeleteException
     */
    public function deleteById(int $entityId): bool;
}
