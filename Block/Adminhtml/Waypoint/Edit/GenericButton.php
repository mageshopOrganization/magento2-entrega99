<?php
/**
 * MageShop | Entrega99
 *
 * @category MageShop
 * @package  Entrega99
 */

declare(strict_types=1);

namespace MageShop\Entrega99\Block\Adminhtml\Waypoint\Edit;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Registry;

abstract class GenericButton
{
    public function __construct(
        protected readonly Context $context,
        protected readonly Registry $registry
    ) {
    }

    protected function getWaypointId(): ?int
    {
        $waypoint = $this->registry->registry('entrega99_waypoint');
        if ($waypoint && $waypoint->getWaypointId()) {
            return (int)$waypoint->getWaypointId();
        }
        return null;
    }

    protected function getUrl(string $route, array $params = []): string
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }
}
