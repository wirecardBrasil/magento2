/**
 * Copyright © Moip by PagSeguro. All rights reserved.
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */
define(
    [
        "uiComponent",
        "Magento_Checkout/js/model/payment/renderer-list"
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        let config = window.checkoutConfig.payment,
            boletoType = 'moip_magento2_boleto',
            ccType = 'moip_magento2_cc';

        if (config[boletoType].isActive) {
            rendererList.push(
                {
                    type: boletoType,
                    component: "Moip_Magento2/js/view/payment/method-renderer/moip_magento2_boleto"
                }
            );
        }

        if (config[ccType].isActive) {
            rendererList.push(
                {
                    type: ccType,
                    component: "Moip_Magento2/js/view/payment/method-renderer/moip_magento2_cc"
                }
            );
        }
        /** Add view logic here if needed */
        return Component.extend({});
    }
);
