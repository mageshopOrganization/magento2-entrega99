/**
 * MageShop | Entrega99
 * Required address fields for a 99Entrega rate request.
 */
define([], function () {
    'use strict';
    return {
        getRules: function () {
            return {
                'country_id': {
                    'required': true
                },
                'postcode': {
                    'required': true
                }
            };
        }
    };
});
