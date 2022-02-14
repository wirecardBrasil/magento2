/**
 * Copyright © Moip by PagSeguro. All rights reserved.
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */
define(
    [
        "Magento_Checkout/js/view/summary/abstract-total",
        "Magento_Checkout/js/model/quote",
        "Magento_Catalog/js/price-utils",
        "Magento_Checkout/js/model/totals",
        "mage/translate",
    ],
    function (Component, quote, priceUtils, totals, $t) {
        "use strict";
        return Component.extend({
            defaults: {
                template: "Moip_Magento2/checkout/summary/moip_interest",
                active: false
            },
            totals: quote.getTotals(),

            initObservable() {
                this._super().observe(["active"]);
                return this;
            },

            isActive() {
                return this.getPureValue() !== 0;
            },

            getPureValue() {
                var price = 0,
                    priceInterest;
                if (this.totals() && totals.getSegment("moip_interest_amount")) {
                    priceInterest = totals.getSegment("moip_interest_amount").value
                    if (priceInterest !== 0.000) {
                        return priceInterest;
                    }
                }
                return price;
            },

            customTitle() {
                if (this.getPureValue() > 0) {
                    return $t("Installment Interest");
                }
                return $t("Discount Cash");
            },

            getValue() {
                return this.getFormattedPrice(this.getPureValue());
            },

            getBaseValue() {
                var price = 0;
                if (this.totals()) {
                    price = this.totals().base_payment_charge;
                }
                return priceUtils.formatPrice(price, quote.getBasePriceFormat());
            }
        });
    }
);