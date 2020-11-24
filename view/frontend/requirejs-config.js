/**
 * Copyright © Wirecard Brasil. All rights reserved.
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */
var config = {
    config: {
        mixins: {
            "Magento_Payment/js/model/credit-card-validation/credit-card-number-validator/credit-card-type": {
                "Moip_Magento2/js/mixins/credit-card-type-mixin": true
            }
        }
    }
};