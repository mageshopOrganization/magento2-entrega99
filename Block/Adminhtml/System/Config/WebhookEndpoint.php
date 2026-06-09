<?php
/**
 * MageShop | Entrega99
 *
 * @category MageShop
 * @package  Entrega99
 */

declare(strict_types=1);

namespace MageShop\Entrega99\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Renders the public webhook URL in the admin config screen (read-only).
 * Copy/paste into the 99Entrega portal webhook configuration.
 */
class WebhookEndpoint extends Field
{
    private StoreManagerInterface $storeManager;

    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->storeManager = $storeManager;
    }

    protected function _getElementHtml(AbstractElement $element): string
    {
        $baseUrl = rtrim((string)$this->storeManager->getStore()->getBaseUrl(), '/');
        $endpoint = $baseUrl . '/rest/V1/entrega99/webhook';

        return sprintf(
            '<input type="text" readonly value="%s" style="width: 100%%;" onclick="this.select()"/>',
            $this->escapeHtmlAttr($endpoint)
        );
    }
}
