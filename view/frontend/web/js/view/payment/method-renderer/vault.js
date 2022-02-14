/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    "underscore",
    "jquery",
    "Magento_Vault/js/view/payment/method-renderer/vault",
    "Magento_Payment/js/model/credit-card-validation/credit-card-data",
    "Magento_Checkout/js/model/quote",
    "Magento_Catalog/js/price-utils",
    "mage/translate",
    "ko",
    "Moip_Magento2/js/action/checkout/cart/totals"
], function (_, $, VaultComponent, creditCardData, quote, priceUtils, $t, ko, TotalsMoipInterest) {
    "use strict";

    return VaultComponent.extend({
        defaults: {
            active: false,
            template: "Moip_Magento2/payment/vault",
            vaultForm: "Moip_Magento2/payment/vault-form",
            creditCardInstallment: ""
        },
        totals: quote.getTotals(),

        initialize() {
            var self = this;
            this._super();

            this.creditCardInstallment.subscribe(function (value) {
                creditCardData.creditCardInstallment = value;
                self.genetateInterest();
            });
        },

        initObservable() {
            this._super().observe(["active", "creditCardInstallment"]);
            return this;
        },

        isShowLegend() {
            return true;
        },

        getData() {
            var data = {
                'method': this.getCode(),
                "additional_data": {
                    "cc_cid": $("#" + this.getId() + '_cc_cid').val(),
                    "cc_installments": $("#" + this.getId() + '_installments').val(),
                    'public_hash': this.getToken()
                }
            };

            return data;
        },

        genetateInterest() {
            var value = this.creditCardInstallment();
            if (value) {
                TotalsMoipInterest.save(value);
            }
        },

        beforePlaceOrder() {
            this.genetateInterest();
            if (!$(this.formElement).valid()) {
                return;
            } else {
                this.placeOrder();
            }
        },

        isActive() {
            var active = this.getId() === this.isChecked();
            this.active(active);
            return active;
        },

        initFormElement(element) {
            this.formElement = element;
            $(this.formElement).validation();
        },

        getAuxiliaryCode() {
            return "moip_magento2_cc";
        },

        getCode() {
            return "moip_magento2_cc_vault";
        },

        getToken() {
            return this.publicHash;
        },

        getMaskedCard() {
            return this.details["cc_last4"];
        },

        getExpirationDate() {
            return this.details["cc_exp_month"] + "/" + this.details["cc_exp_year"];
        },

        getCardType() {
            return this.details["cc_type"];
        },

        hasVerification() {
            return window.checkoutConfig.payment[this.getCode()].useCvv;
        },

        getIcons(type) {
            return window.checkoutConfig.payment[this.getCode()].icons.hasOwnProperty(type) ?
                window.checkoutConfig.payment[this.getCode()].icons[type]
                : false;
        },

        getInterestApply() {
            var valueInterest = 0;
            _.map(this.totals()['total_segments'], function (segment) {
                if (segment['code'] === 'moip_interest_amount') {
                    valueInterest = segment['value'];
                }
            });
            return valueInterest;
        },

        getInstalmentsValues() {
            var grandTotal = quote.totals().base_grand_total;
            var moipIterest = this.getInterestApply();
            var calcTotal = grandTotal - moipIterest;
            var type_interest = window.checkoutConfig.payment[this.getAuxiliaryCode()].type_interest
            var info_interest = window.checkoutConfig.payment[this.getAuxiliaryCode()].info_interest;
            var min_installment = window.checkoutConfig.payment[this.getAuxiliaryCode()].min_installment;
            var max_installment = window.checkoutConfig.payment[this.getAuxiliaryCode()].max_installment;
            var installmentsCalcValues = {};
            var max_div = (calcTotal / min_installment);
            max_div = parseInt(max_div);
            if (max_div > max_installment) {
                max_div = max_installment;
            } else {
                if (max_div > 12) {
                    max_div = 12;
                }
            }
            var limit = max_div;

            if (limit === 0) {
                limit = 1;
            }

            for (var i = 1; i < info_interest.length; i++) {
                if (i > limit) {
                    break;
                }
                var interest = info_interest[i];
                if (interest > 0) {
                    var taxa = interest / 100;
                    if (type_interest === "compound") {
                        var pw = Math.pow((1 / (1 + taxa)), i);
                        var installment = (((calcTotal * taxa) * 1) / (1 - pw));
                    } else {
                        var installment = ((calcTotal * taxa) + calcTotal) / i;
                    }
                    var totalInstallment = installment * i;
                    if (installment > 5 && installment > min_installment) {
                        installmentsCalcValues[i] = {
                            "installment": priceUtils.formatPrice(installment, quote.getPriceFormat()),
                            "totalInstallment": priceUtils.formatPrice(totalInstallment, quote.getPriceFormat()),
                            "totalInterest": priceUtils.formatPrice(totalInstallment - calcTotal, quote.getPriceFormat()),
                            "interest": interest
                        };
                    }
                } else if (interest == 0) {
                    if (calcTotal > 0) {
                        installmentsCalcValues[i] = {
                            "installment": priceUtils.formatPrice((calcTotal / i), quote.getPriceFormat()),
                            "totalInstallment": priceUtils.formatPrice(calcTotal, quote.getPriceFormat()),
                            "totalInterest": 0,
                            "interest": 0
                        };
                    }
                } else if (interest < 0) {
                    var taxa = interest / 100;
                    if (calcTotal > 0) {
                        var installment = ((calcTotal * taxa) + calcTotal) / i;
                        installmentsCalcValues[i] = {
                            "totalWithTheDiscount": priceUtils.formatPrice(installment, quote.getPriceFormat()),
                            "discount": interest,
                            "interest": interest
                        };
                    }
                }
            }
            return installmentsCalcValues;
        },

        getInstallments() {
            var temp = _.map(this.getInstalmentsValues(), function (value, key) {
                var inst;

                if (value["interest"] === 0) {
                    inst = $t("%1x of %2 not interest").replace("%1", key).replace("%2", value["installment"]);
                } else if (value["interest"] < 0) {
                    inst = $t("%1% of discount cash with total of %2").replace("%1", value["discount"]).replace("%2", value["totalWithTheDiscount"]);
                } else {
                    inst = $t("%1x of %2 in the total value of %3").replace("%1", key).replace("%2", value["installment"]).replace("%3", value["totalInstallment"]);
                }

                return {
                    "value": key,
                    "installments": inst
                };
            });

            var newArray = [];
            for (var i = 0; i < temp.length; i++) {
                if (temp[i].installments != "undefined" && temp[i].installments != undefined) {
                    newArray.push(temp[i]);
                }
            }

            return newArray;
        },
    });
});