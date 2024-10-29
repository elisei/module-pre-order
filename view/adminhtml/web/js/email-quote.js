define([
    'jquery',
    'O2TI_PreOrder/js/admin-order-common',
    'Magento_Ui/js/modal/alert',
], function ($, adminOrderCommon, alert) {
    'use strict';

    return {
        url: false,
        emailQuoteButton: false,

        init: function (url) {
            this.url = url;
            this.emailQuoteButton = $('#email_quote');
            this.emailQuoteButton.click(this.onSendEmailClicked.bind(this));
        },

        onSendEmailClicked: function () {
            let promise = adminOrderCommon.save();
            let afterSendQuoteEmailPromise = promise.then(this.sendQuoteEmail.bind(this, this.url));
            afterSendQuoteEmailPromise.done(this.onEmailSent.bind(this));
            afterSendQuoteEmailPromise.fail(this.onEmailFail.bind(this));
        },

        sendQuoteEmail: function (url) {
            adminOrderCommon.startLoader();
            return $.ajax({
                url: url,
                method: 'POST',
                data: {'quote_id': window.order.quoteId},
                dataType: 'json',
                error: null,
                complete: null
            });
        },

        onEmailSent: function (response) {
            adminOrderCommon.stopLoader();
            if (response.success) {
                window.location.href = response.redirect_url;
            }
        },

        onEmailFail: function () {
            adminOrderCommon.stopLoader();
            window.location.reload();
        }
    };
});