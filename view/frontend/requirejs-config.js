/**
 * Copyright © Moip by PagSeguro. All rights reserved.
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */
var config = {
    config: {
        mixins: {
            "Magento_Payment/js/model/credit-card-validation/credit-card-number-validator/credit-card-type": {
                "Moip_Magento2/js/mixins/credit-card-type-mixin": true
            },
            "Magento_Checkout/js/action/select-payment-method": {
                "Moip_Magento2/js/mixins/select-payment-method-mixin": true
            }
        }
    }
};