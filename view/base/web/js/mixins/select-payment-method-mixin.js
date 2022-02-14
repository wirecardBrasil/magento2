/**
 * Copyright © Moip by PagSeguro. All rights reserved.
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    "jquery",
    "mage/utils/wrapper",
    "Moip_Magento2/js/action/checkout/cart/totals"
], function ($, wrapper, totalsMoipInterest) {
    "use strict";

    return function (selectPaymentMethodAction) {

        return wrapper.wrap(selectPaymentMethodAction, function (originalSelectPaymentMethodAction, paymentMethod) {

            originalSelectPaymentMethodAction(paymentMethod);

            if (paymentMethod === null) {
                return;
            }

            if (window.checkoutConfig.payment["moip_magento2_cc"].isActive) {
                if (window.checkoutConfig.payment["moip_magento2_cc"].info_interest[1] < 0) {
                    totalsMoipInterest.save(0);
                }
            }
            return;
        });
    };

});
