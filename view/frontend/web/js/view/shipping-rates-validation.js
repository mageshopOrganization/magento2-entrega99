/**
 * MageShop | Entrega99
 * Registers the 99Entrega rate validator with Magento's shipping-rates-validator framework.
 */
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/shipping-rates-validator',
        'Magento_Checkout/js/model/shipping-rates-validation-rules',
        'MageShop_Entrega99/js/model/shipping-rates-validator',
        'MageShop_Entrega99/js/model/shipping-rates-validation-rules'
    ],
    function (
        Component,
        defaultShippingRatesValidator,
        defaultShippingRatesValidationRules,
        entrega99ShippingRatesValidator,
        entrega99ShippingRatesValidationRules
    ) {
        'use strict';
        defaultShippingRatesValidator.registerValidator('entrega99', entrega99ShippingRatesValidator);
        defaultShippingRatesValidationRules.registerRules('entrega99', entrega99ShippingRatesValidationRules);
        return Component;
    }
);
