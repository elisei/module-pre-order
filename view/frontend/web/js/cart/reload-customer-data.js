/**
 * O2TI Pre Order.
 *
 * Copyright © 2024 O2TI. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

define([
    'Magento_Customer/js/customer-data',
    'jquery',
    'Magento_Checkout/js/model/quote'
], function (customerData, $, quote) {
    'use strict';

    return function () {
        if (window.location.hash === '#pre-order') {
            var sections = [
                    'cart',
                    'checkout-data',
                    'directory-data',
                    'messages',
                    'wishlist',
                    'recently_viewed_product',
                    'recently_compared_product'
                ],
                cleanUrl;

            $(() => {
                $('body').trigger('processStart');

                if (customerData && typeof customerData.reload === 'function') {
                    customerData.reload(sections, true)
                        .done(() => {
                            try {
                                quote.shippingMethod(null);

                                if (window.history && window.history.replaceState) {
                                    cleanUrl = window.location.href.split('#')[0];
                                    window.history.replaceState({}, document.title, cleanUrl);
                                }
                            } catch (e) {
                                console.error('Error processing quote data:', e);
                            }
                        })
                        .fail((error) => {
                            console.error('Failed to reload customer data:', error);
                        })
                        .always(() => {
                            $('body').trigger('processStop');
                        });
                } else {
                    console.error('Customer data module not properly initialized');
                    $('body').trigger('processStop');
                }
            });
        }
    };
});