/**
 * Copyright © Moip by PagSeguro. All rights reserved.
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */
define([
    "underscore",
    "jquery",
    "Magento_Payment/js/view/payment/cc-form",
    "Magento_Vault/js/view/payment/vault-enabler",
    "Magento_Payment/js/model/credit-card-validation/credit-card-data",
    "Moip_Magento2/js/view/payment/gateway/custom-validation",
    "Moip_Magento2/js/view/payment/lib/jquery/jquery.mask",
    "Magento_Checkout/js/model/quote",
    "Magento_Catalog/js/price-utils",
    "mage/translate",
    "ko",
    "mage/calendar",
    "Moip_Magento2/js/action/checkout/cart/totals",
    "Moip_Magento2/js/view/payment/gateway/moip",
    "Magento_Payment/js/model/credit-card-validation/validator",
], function (_, $, Component, VaultEnabler, creditCardData, custom, mask, quote, priceUtils, $t, ko, calendar, TotalsMoipInterest) {
    "use strict";

    return Component.extend({
        defaults: {
            active: false,
            template: "Moip_Magento2/payment/cc",
            ccForm: "Moip_Magento2/payment/cc-form",
            creditCardInstallment: "",
            creditCardHash: "",
            creditCardHolderFullName: "",
            creditCardHolderTaxDocument: "",
            creditCardHolderPhone: "",
            creditCardHolderBirthDate: ""
        },
        totals: quote.getTotals(),

        initObservable() {
            this._super().observe(["active", "creditCardInstallment", "creditCardHash", "creditCardHolderFullName", "creditCardHolderTaxDocument", "creditCardHolderPhone", "creditCardHolderBirthDate"]);
            return this;
        },

        getCode() {
            return "moip_magento2_cc";
        },

        initialize() {
            var self = this;

            this.vaultEnabler = new VaultEnabler();
            this.vaultEnabler.setPaymentCode(this.getVaultCode());


            var vat = $("#moip_magento2_cc_cc_tax_document");
            var tel = $("#moip_magento2_cc_cc_holder_phone");
            var dob = $("#moip_magento2_cc_cc_holder_birth_date");
            this._super();

            ko.bindingHandlers.datepicker = {
                init: function (element, valueAccessor, allBindingsAccessor) {
                    var $el = $(element);
                    var options = {
                        dateFormat: "dd/mm/yy",
                        showButtonPanel: false,
                        hideIfNoPrevNext: true,
                        endDate: "-18Y",
                        setStartDate: "-18Y",
                        minDate: "-99Y",
                        maxDate: "-18Y",
                        yearRange: "-99:-18",
                        changeMonth: true,
                        changeYear: true,
                    };
                    $el.datepicker(options);
                    var writable = valueAccessor();
                    if (!ko.isObservable(writable)) {
                        var propWriters = allBindingsAccessor()._ko_property_writers;
                        if (propWriters && propWriters.datepicker) {
                            writable = propWriters.datepicker;
                        } else {
                            return;
                        }
                    }
                    writable($(element).datepicker("getDate"));
                },
                update: function (element, valueAccessor) {
                    var widget = $(element).data("DateTimePicker");
                    if (widget) {
                        var date = ko.utils.unwrapObservable(valueAccessor());
                        widget.date(date);
                    }
                }
            };

            dob.mask("00/00/0000", { clearIfNotMatch: true });
            tel.mask("(00)00000-0000", { clearIfNotMatch: true });



            this.creditCardInstallment.subscribe(function (value) {
                creditCardData.creditCardInstallment = value;
                self.genetateInterest();
            });

            this.creditCardHash.subscribe(function (value) {
                creditCardData.creditCardHash = value;
            });

            this.creditCardHolderFullName.subscribe(function (value) {
                creditCardData.creditCardHolderFullName = value;
            });

            this.creditCardHolderTaxDocument.subscribe(function (value) {
                var typeMaskVat = value.replace(/\D/g, "").length <= 11 ? "000.000.000-009" : "00.000.000/0000-00";
                vat.mask(typeMaskVat, { clearIfNotMatch: true });
                creditCardData.creditCardHolderTaxDocument = value;
            });

            this.creditCardHolderPhone.subscribe(function (value) {
                creditCardData.creditCardHolderPhone = value;
            });

            this.creditCardHolderBirthDate.subscribe(function (value) {
                creditCardData.creditCardHolderBirthDate = value;
            });

            this.selectedCardType.subscribe(function (value) {
                $("#moip_magento2_cc_number").unmask();
                if (value === "VI" || value === "MC" || value === "ELO" || value === "HC" || value === "HI") {
                    $("#moip_magento2_cc_cc_number").mask("0000 0000 0000 0000");
                }
                if (value === "DN") {
                    $("#moip_magento2_cc_cc_number").mask("0000 000000 0000");
                }
                if (value === "AE") {
                    $("#moip_magento2_cc_cc_number").mask("0000 000000 00000");
                }
                creditCardData.selectedCardType = value;
            });
        },

        isActive() {
            var active = this.getCode() === this.isChecked();
            this.active(active);
            return active;
        },

        initFormElement(element) {
            this.formElement = element;
            $(this.formElement).validation();
        },

        genetateInterest() {
            var value = this.creditCardInstallment();
            if (value) {
                TotalsMoipInterest.save(value);
            }
        },

        beforePlaceOrder() {
            this.genetateInterest();
            this.getHash();
            if (!$(this.formElement).valid()) {
                return;
            } else {
                this.placeOrder();
            }
        },

        getHash() {
            var cc = new Moip.CreditCard({
                number: this.creditCardNumber(),
                cvc: this.creditCardVerificationNumber(),
                expMonth: this.creditCardExpMonth(),
                expYear: this.creditCardExpYear(),
                pubKey: this.getPublickey()
            });
            if (cc.isValid()) {
                this.creditCardHash(cc.hash());
            }
        },

        getData() {
            var data = {
                'method': this.getCode(),
                "additional_data": {
                    "cc_number": this.creditCardNumber().substr(-4),
                    "cc_type": this.creditCardType(),
                    "cc_exp_month": this.creditCardExpMonth(),
                    "cc_exp_year": this.creditCardExpYear(),
                    "cc_installments": this.creditCardInstallment(),
                    "cc_hash": this.creditCardHash(),
                    "cc_holder_fullname": this.creditCardHolderFullName(),
                    "cc_holder_tax_document": this.creditCardHolderTaxDocument(),
                    "cc_holder_phone": this.creditCardHolderPhone(),
                    "cc_holder_birth_date": this.creditCardHolderBirthDate()
                }
            };
            data['additional_data'] = _.extend(data['additional_data'], this.additionalData);
            this.vaultEnabler.visitAdditionalData(data);
            return data;
        },

        getPublickey() {
            return window.checkoutConfig.payment[this.getCode()].public_key;
        },

        hasVerification() {
            return window.checkoutConfig.payment[this.getCode()].useCvv;
        },

        getTitle() {
            return window.checkoutConfig.payment[this.getCode()].title;
        },

        getLogo() {
            return window.checkoutConfig.payment[this.getCode()].logo;
        },

        getIcons(type) {
            return window.checkoutConfig.payment[this.getCode()].icons.hasOwnProperty(type) ?
                window.checkoutConfig.payment[this.getCode()].icons[type]
                : false;
        },

        isShowLegend() {
            return true;
        },

        TaxDocumentCapture() {
            return window.checkoutConfig.payment[this.getCode()].tax_document_capture;
        },

        BirthDateCapture() {
            return window.checkoutConfig.payment[this.getCode()].birth_date_capture;
        },

        PhoneCapture() {
            return window.checkoutConfig.payment[this.getCode()].phone_capture;
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
            var type_interest = window.checkoutConfig.payment[this.getCode()].type_interest
            var info_interest = window.checkoutConfig.payment[this.getCode()].info_interest;
            var min_installment = window.checkoutConfig.payment[this.getCode()].min_installment;
            var max_installment = window.checkoutConfig.payment[this.getCode()].max_installment;
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

        getVaultCode() {
            return window.checkoutConfig.payment[this.getCode()].ccVaultCode;
        },

        isVaultEnabled: function () {
            return this.vaultEnabler.isVaultEnabled();
        },
    });
});