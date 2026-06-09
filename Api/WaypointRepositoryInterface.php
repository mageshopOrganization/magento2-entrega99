<?php
/**
 * MageShop | Entrega99
 *
 * @category MageShop
 * @package  Entrega99
 */

declare(strict_types=1);

namespace MageShop\Entrega99\Api;

use MageShop\Entrega99\Api\Data\WaypointInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

interface WaypointRepositoryInterface
{
    /**
     * @throws CouldNotSaveException
     */
    public function save(WaypointInterface $waypoint): WaypointInterface;

    /**
     * @throws NoSuchEntityException
     */
    public function getById(int $waypointId): WaypointInterface;

    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface;

    /**
     * Returns the first active waypoint matching the given store_id (and optional MSI source).
     * Falls back to a waypoint with no store_id set if none matches the given store.
     */
    public function getFirstActive(?int $storeId = null, ?string $msiSourceCode = null): ?WaypointInterface;

    /**
     * @throws CouldNotDeleteException
     */
    public function delete(WaypointInterface $waypoint): bool;

    /**
     * @throws NoSuchEntityException
     * @throws CouldNotDeleteException
     */
    public function deleteById(int $waypointId): bool;
}
