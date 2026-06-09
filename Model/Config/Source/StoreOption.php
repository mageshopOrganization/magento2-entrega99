<?php
/**
 * MageShop | Entrega99
 *
 * @category MageShop
 * @package  Entrega99
 */

declare(strict_types=1);

namespace MageShop\Entrega99\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Store\Api\StoreRepositoryInterface;

/**
 * Lists all store views as select options, with a leading "All Stores" entry
 * (empty value) so a waypoint can be unscoped.
 */
class StoreOption implements OptionSourceInterface
{
    public function __construct(
        private readonly StoreRepositoryInterface $storeRepository
    ) {
    }

    public function toOptionArray(): array
    {
        $options = [['value' => '', 'label' => __('All Stores')]];
        foreach ($this->storeRepository->getList() as $store) {
            if ((int)$store->getId() === 0) {
                continue; // skip admin store
            }
            $options[] = [
                'value' => (int)$store->getId(),
                'label' => sprintf('%s (id=%d)', $store->getName(), $store->getId()),
            ];
        }
        return $options;
    }
}
