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
use MageShop\Entrega99\Api\Data\TokenInterfaceFactory;
use MageShop\Entrega99\Api\TokenRepositoryInterface;
use MageShop\Entrega99\Model\ResourceModel\Token as TokenResource;
use MageShop\Entrega99\Model\ResourceModel\Token\CollectionFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class TokenRepository implements TokenRepositoryInterface
{
    public function __construct(
        private readonly TokenResource $resource,
        private readonly TokenInterfaceFactory $tokenFactory,
        private readonly CollectionFactory $collectionFactory
    ) {
    }

    public function save(TokenInterface $token): TokenInterface
    {
        try {
            $this->resource->save($token);
        } catch (\Throwable $e) {
            throw new CouldNotSaveException(__('Could not save token: %1', $e->getMessage()), $e);
        }
        return $token;
    }

    public function getById(int $entityId): TokenInterface
    {
        /** @var Token $token */
        $token = $this->tokenFactory->create();
        $this->resource->load($token, $entityId);
        if (!$token->getEntityId()) {
            throw new NoSuchEntityException(__('Token with id %1 was not found.', $entityId));
        }
        return $token;
    }

    public function findByStoreAndScope(int $storeId, string $scope): ?TokenInterface
    {
        $collection = $this->collectionFactory->create()
            ->addFieldToFilter('store_id', $storeId)
            ->addFieldToFilter('scope', $scope)
            ->setPageSize(1);

        /** @var Token|false $token */
        $token = $collection->getFirstItem();
        return $token && $token->getEntityId() ? $token : null;
    }

    public function delete(TokenInterface $token): bool
    {
        try {
            $this->resource->delete($token);
        } catch (\Throwable $e) {
            throw new CouldNotDeleteException(__('Could not delete token: %1', $e->getMessage()), $e);
        }
        return true;
    }

    public function deleteById(int $entityId): bool
    {
        return $this->delete($this->getById($entityId));
    }
}
