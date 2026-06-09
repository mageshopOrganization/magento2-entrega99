<?php
/**
 * MageShop | Entrega99
 *
 * @category MageShop
 * @package  Entrega99
 */

declare(strict_types=1);

namespace MageShop\Entrega99\Model\Waypoint;

use MageShop\Entrega99\Model\ResourceModel\Waypoint\Collection;
use MageShop\Entrega99\Model\ResourceModel\Waypoint\CollectionFactory;
use Magento\Ui\DataProvider\AbstractDataProvider;

/**
 * Feeds the entrega99_waypoint_form UI component with the row being edited.
 */
class DataProvider extends AbstractDataProvider
{
    /** @var array */
    protected $loadedData;

    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        CollectionFactory $collectionFactory,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collectionFactory->create();
    }

    public function getData(): array
    {
        if ($this->loadedData !== null) {
            return $this->loadedData;
        }

        $this->loadedData = [];
        /** @var Collection $collection */
        $collection = $this->collection;
        foreach ($collection->getItems() as $waypoint) {
            $this->loadedData[$waypoint->getWaypointId()] = $waypoint->getData();
        }

        return $this->loadedData;
    }
}
