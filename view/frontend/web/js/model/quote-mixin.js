define([
    'mage/utils/wrapper',
    'Magento_Checkout/js/model/url-builder',
    'Magento_Customer/js/customer-data'
], function (wrapper, urlBuilder, customerData) {
    'use strict';

    return function (quote) {
        quote.setShippingAddress = wrapper.wrap(quote.setShippingAddress, function (original, address) {
            if (address && address.regionId && !address.regionCode) {
                var regions = customerData.get('directory-data')().regions;
                if (regions && regions[address.countryId]) {
                    var region = regions[address.countryId].filter(function(region) {
                        return region.id == address.regionId;
                    })[0];
                    
                    if (region) {
                        address.regionCode = region.code;
                        address.region = region.name;
                    }
                }
            }
            return original(address);
        });

        quote.setBillingAddress = wrapper.wrap(quote.setBillingAddress, function (original, address) {
            if (address && address.regionId && !address.regionCode) {
                var regions = customerData.get('directory-data')().regions;
                if (regions && regions[address.countryId]) {
                    var region = regions[address.countryId].filter(function(region) {
                        return region.id == address.regionId;
                    })[0];
                    
                    if (region) {
                        address.regionCode = region.code;
                        address.region = region.name;
                    }
                }
            }
            return original(address);
        });

        return quote;
    };
});