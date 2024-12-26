define([
    'Magento_Customer/js/customer-data',
    'jquery',
    'Magento_Checkout/js/model/quote',
    'Magento_Customer/js/model/customer'
], function (customerData, $, quote, customer) {
    'use strict';

    return function () {
        if (window.location.hash === '#pre-order') {
            let sections = [
                'cart',
                'checkout-data',
                'directory-data',
                'messages',
                'wishlist',
                'recently_viewed_product',
                'recently_compared_product',
                'customer'
            ],
            cleanUrl = window.location.href.split('#')[0];

            $(() => {
                $('body').trigger('processStart');

                customerData.invalidate(['customer']);

                if (customerData && typeof customerData.reload === 'function') {
                    customerData.reload(sections, true)
                        .done(() => {
                            try {
                                quote.shippingMethod(null);

                                const customerInfo = customerData.get('customer')();

                                if (customerInfo && customerInfo.firstname) {
                                    customer.setIsLoggedIn(true);
                                }

                                if (window.history && window.history.replaceState) {
                                    window.history.replaceState({}, document.title, cleanUrl);
                                }

                                if (!customer.isLoggedIn() && customerInfo && customerInfo.firstname) {
                                    window.location.reload();
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
