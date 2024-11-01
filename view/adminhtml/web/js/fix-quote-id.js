/**
 * O2TI Pre Order.
 *
 * Copyright © 2024 O2TI. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

define([
    'Magento_Sales/order/create/form'
], function () {
    'use strict';

    return {
        init: function (quoteId) {
            window.order.quoteId = quoteId;
        }
    };
});
