define([
    'mage/utils/wrapper',
    'Magento_Checkout/js/model/url-builder',
    'Magento_Customer/js/customer-data'
], function (wrapper, urlBuilder, customerData) {
    'use strict';

    return function (quote) {
        function updateAddressRegion(address, regions) {
            if (!regions || !regions[address.countryId]) {
                return false;
            }

            var matchedRegion = regions[address.countryId].filter(function (regionItem) {
                return regionItem.id === address.regionId;
            })[0];

            if (matchedRegion) {
                address.regionCode = matchedRegion.code;
                address.region = matchedRegion.name;
                return true;
            }

            return false;
        }

        quote.setShippingAddress = wrapper.wrap(quote.setShippingAddress, function (original, address) {
            if (address && address.regionId && !address.regionCode) {
                var regions = customerData.get('directory-data')().regions;
                updateAddressRegion(address, regions);
            }
            return original(address);
        });

        quote.setBillingAddress = wrapper.wrap(quote.setBillingAddress, function (original, address) {
            if (address && address.regionId && !address.regionCode) {
                var regions = customerData.get('directory-data')().regions;
                updateAddressRegion(address, regions);
            }
            return original(address);
        });

        return quote;
    };
});
