var config = {
    config: {
        mixins: {
            'Magento_Checkout/js/model/quote': {
                'O2TI_PreOrder/js/model/quote-mixin': true
            }
        }
    },
    map: {
        '*': {
            'O2TI_PreOrder_reloadCustomerData': 'O2TI_PreOrder/js/cart/reload-customer-data'
        }
    }
};
