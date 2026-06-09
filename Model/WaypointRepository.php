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
use MageShop\Entrega99\Api\Data\WaypointInterfaceFactory;
use MageShop\Entrega99\Api\WaypointRepositoryInterface;
use MageShop\Entrega99\Model\ResourceModel\Waypoint as WaypointResource;
use MageShop\Entrega99\Model\ResourceModel\Waypoint\Collection;
use MageShop\Entrega99\Model\ResourceModel\Waypoint\CollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class WaypointRepository implements WaypointRepositoryInterface
{
    public function __construct(
        private readonly WaypointResource $resource,
        private readonly WaypointInterfaceFactory $waypointFactory,
        private readonly CollectionFactory $collectionFactory,
        private readonly CollectionProcessorInterface $collectionProcessor,
        private readonly SearchResultsInterfaceFactory $searchResultsFactory
    ) {
    }

    public function save(WaypointInterface $waypoint): WaypointInterface
    {
        try {
            $this->resource->save($waypoint);
        } catch (\Throwable $e) {
            throw new CouldNotSaveException(__('Could not save waypoint: %1', $e->getMessage()), $e);
        }
        return $waypoint;
    }

    public function getById(int $waypointId): WaypointInterface
    {
        /** @var Waypoint $waypoint */
        $waypoint = $this->waypointFactory->create();
        $this->resource->load($waypoint, $waypointId);
        if (!$waypoint->getWaypointId()) {
            throw new NoSuchEntityException(__('Waypoint with id %1 was not found.', $waypointId));
        }
        return $waypoint;
    }

    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface
    {
        /** @var Collection $collection */
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);

        $results = $this->searchResultsFactory->create();
        $results->setSearchCriteria($searchCriteria);
        $results->setItems($collection->getItems());
        $results->setTotalCount($collection->getSize());

        return $results;
    }

    public function getFirstActive(?int $storeId = null, ?string $msiSourceCode = null): ?WaypointInterface
    {
        /** @var Collection $collection */
        $collection = $this->collectionFactory->create()
            ->addFieldToFilter('active', 1)
            ->setPageSize(1);

        if ($msiSourceCode !== null) {
            $collection->addFieldToFilter('msi_source_code', $msiSourceCode);
        } elseif ($storeId !== null) {
            // First try waypoint scoped to the given store; fall back to any unscoped waypoint
            $collection->addFieldToFilter(
                'store_id',
                [
                    ['eq' => $storeId],
                    ['null' => true],
                ]
            )->setOrder('store_id', \Magento\Framework\DB\Select::SQL_DESC); // prefer scoped over unscoped
        }

        /** @var Waypoint|false $waypoint */
        $waypoint = $collection->getFirstItem();
        return $waypoint && $waypoint->getWaypointId() ? $waypoint : null;
    }

    public function delete(WaypointInterface $waypoint): bool
    {
        try {
            $this->resource->delete($waypoint);
        } catch (\Throwable $e) {
            throw new CouldNotDeleteException(__('Could not delete waypoint: %1', $e->getMessage()), $e);
        }
        return true;
    }

    public function deleteById(int $waypointId): bool
    {
        return $this->delete($this->getById($waypointId));
    }
}
