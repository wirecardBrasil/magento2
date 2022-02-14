/**
 * Copyright © Moip by PagSeguro. All rights reserved.
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */
define(
    [
        "Moip_Magento2/js/view/checkout/summary/moip_interest"
    ],
    function (Component) {
        "use strict";

        return Component.extend({

            /**
             * @override
             */
            isDisplayed() {
                return this.getPureValue() != 0;
            },
        });
    }
);