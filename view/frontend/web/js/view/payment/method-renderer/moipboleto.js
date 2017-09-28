/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        'Magento_Checkout/js/view/payment/default'
    ],
    function (Component) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Moip_Magento2/payment/boleto'
            },

             /** Returns send check to info */
            getInstruction: function() {
                return window.checkoutConfig.payment.moipboleto.instruction;
            },

            /** Returns payable to info */
            getDue: function() {
                return window.checkoutConfig.payment.moipboleto.due;
            }

           
        });
    }
);