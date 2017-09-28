define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'moipcc',
                component: 'Moip_Magento2/js/view/payment/method-renderer/moipcc'
            }
        );
        return Component.extend({});
    }
);  