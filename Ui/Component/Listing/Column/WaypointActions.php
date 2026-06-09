<?php
/**
 * MageShop | Entrega99
 *
 * @category MageShop
 * @package  Entrega99
 */

declare(strict_types=1);

namespace MageShop\Entrega99\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Edit/Delete actions for each row in the waypoint grid.
 */
class WaypointActions extends Column
{
    private const URL_PATH_EDIT = 'entrega99/waypoint/edit';
    private const URL_PATH_DELETE = 'entrega99/waypoint/delete';

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private readonly UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource): array
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        foreach ($dataSource['data']['items'] as &$item) {
            if (empty($item['waypoint_id'])) {
                continue;
            }
            $item[$this->getData('name')] = [
                'edit' => [
                    'href'  => $this->urlBuilder->getUrl(self::URL_PATH_EDIT, ['waypoint_id' => $item['waypoint_id']]),
                    'label' => (string)__('Edit'),
                ],
                'delete' => [
                    'href'    => $this->urlBuilder->getUrl(self::URL_PATH_DELETE, ['waypoint_id' => $item['waypoint_id']]),
                    'label'   => (string)__('Delete'),
                    'confirm' => [
                        'title'   => (string)__('Delete %1', $item['name'] ?? ''),
                        'message' => (string)__('Are you sure you want to delete this pickup location?'),
                    ],
                    'post' => true,
                ],
            ];
        }
        return $dataSource;
    }
}
