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

/**
 * Time-of-day options in 30-minute increments, used by the waypoint form
 * for opening/closing hours per weekday.
 */
class HourOption implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        $options = [['value' => '', 'label' => __('— (closed)')]];
        for ($h = 0; $h < 24; $h++) {
            foreach ([0, 30] as $m) {
                $time = sprintf('%02d:%02d', $h, $m);
                $options[] = ['value' => $time, 'label' => $time];
            }
        }
        return $options;
    }
}
