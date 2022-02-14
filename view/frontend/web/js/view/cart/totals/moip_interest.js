/**
 * Copyright © Moip by PagSeguro. All rights reserved.
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */
/*global define*/
define(
    [
        "Moip_Magento2/js/view/cart/summary/moip_interest"
    ],
    function (Component) {
        "use strict";

        return Component.extend({
            /**
             * @override
             *
             * @returns {boolean}
             */
            isDisplayed() {
                return this.getPureValue() !== 0;
            }
        });
    }
);
