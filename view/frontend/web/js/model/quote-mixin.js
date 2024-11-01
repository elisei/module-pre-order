/**
 * O2TI Pre Order.
 *
 * Copyright © 2024 O2TI. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

define([
    'mage/utils/wrapper',
    'Magento_Checkout/js/model/url-builder',
    'Magento_Customer/js/customer-data'
], function (wrapper, urlBuilder, customerData) {
    'use strict';

    return function (quote) {
        quote.setShippingAddress = wrapper.wrap(quote.setShippingAddress, function (original, address) {
            if (address && address.regionId && !address.regionCode) {
                var regions = customerData.get('directory-data')().regions,
                    matchedRegion;

                if (regions && regions[address.countryId]) {
                    matchedRegion = regions[address.countryId].filter(function (regionItem) {
                        return regionItem.id === address.regionId;
                    })[0];

                    if (matchedRegion) {
                        address.regionCode = matchedRegion.code;
                        address.region = matchedRegion.name;
                    }
                }
            }
            return original(address);
        });

        quote.setBillingAddress = wrapper.wrap(quote.setBillingAddress, function (original, address) {
            if (address && address.regionId && !address.regionCode) {
                var regions = customerData.get('directory-data')().regions,
                    matchedRegion;

                if (regions && regions[address.countryId]) {
                    matchedRegion = regions[address.countryId].filter(function (regionItem) {
                        return regionItem.id === address.regionId;
                    })[0];

                    if (matchedRegion) {
                        address.regionCode = matchedRegion.code;
                        address.region = matchedRegion.name;
                    }
                }
            }
            return original(address);
        });

        return quote;
    };
});