/**
 * Copyright © Moip by PagSeguro. All rights reserved.
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */
define(
    [
        "jquery",
        "Magento_Checkout/js/model/quote",
        "Magento_Checkout/js/model/url-builder",
        "Magento_Checkout/js/model/error-processor",
        "mage/url",
        "Magento_Checkout/js/action/get-totals",
        'Magento_Customer/js/model/customer',
    ],
    function ($, quote, urlBuilder, errorProcessor, urlFormatter, getTotalsAction, customer) {
        "use strict";
        return {
            /**
             * Save Moip Interest by Installment
             *
             * @param installment
             */
            save(installment) {
                var serviceUrl,
                    payload,
                    quoteId = quote.getQuoteId();
                if (!customer.isLoggedIn()) {
                    serviceUrl = urlBuilder.createUrl('/guest-carts/:cartId/set-installment-for-moip-interest', {
                        cartId: quoteId
                    });
                    payload = {
                        cartId: quoteId,
                        installment: {
                            installment_for_interest: installment
                        }
                    };
                } else {
                    serviceUrl = urlBuilder.createUrl('/carts/mine/set-installment-for-moip-interest', {});
                    payload = {
                        cartId: quoteId,
                        installment: {
                            installment_for_interest: installment
                        }
                    };
                }

                var result = true;
                $.ajax({
                    url: urlFormatter.build(serviceUrl),
                    data: JSON.stringify(payload),
                    global: false,
                    contentType: "application/json",
                    type: "PUT",
                    async: false
                }).done(
                    function (response) {
                        var deferred = $.Deferred();
                        getTotalsAction([], deferred);
                    }
                ).fail(
                    function (response) {
                        result = false;
                        errorProcessor.process(response);
                    }
                );
                return result;
            }
        };
    });