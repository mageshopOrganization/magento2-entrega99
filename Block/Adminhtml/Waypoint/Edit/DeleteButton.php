<?php
/**
 * MageShop | Entrega99
 *
 * @category MageShop
 * @package  Entrega99
 */

declare(strict_types=1);

namespace MageShop\Entrega99\Block\Adminhtml\Waypoint\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class DeleteButton extends GenericButton implements ButtonProviderInterface
{
    public function getButtonData(): array
    {
        $waypointId = $this->getWaypointId();
        if ($waypointId === null) {
            return [];
        }

        return [
            'label'      => (string)__('Delete'),
            'class'      => 'delete',
            'on_click'   => sprintf(
                "deleteConfirm('%s', '%s')",
                (string)__('Are you sure you want to delete this pickup location?'),
                $this->getUrl('*/*/delete', ['waypoint_id' => $waypointId])
            ),
            'sort_order' => 20,
        ];
    }
}
