/**
 * Copyright © Wirecard Brasil. All rights reserved.
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */
define([
    'Magento_Checkout/js/model/quote',
    'Moip_Magento2/js/action/checkout/cart/totals'
], function (quote, totals) {
    'use strict';

    return function (paymentMethod) {
        if (paymentMethod) {
            paymentMethod.__disableTmpl = {
                title: true
            };
        }
        quote.paymentMethod(paymentMethod);
        totals.save(0);
    };
});