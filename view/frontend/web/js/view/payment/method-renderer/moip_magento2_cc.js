/**
 * Copyright © Wirecard Brasil. All rights reserved.
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
    "Moip_Magento2/js/view/payment/gateway/moip",
    "Magento_Payment/js/model/credit-card-validation/validator"
], function (_, $, Component, VaultEnabler, creditCardData, custom, mask, quote, priceUtils, $t, ko, calendar) {
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

        initObservable() {
            this._super().observe(["active","creditCardInstallment","creditCardHash","creditCardHolderFullName","creditCardHolderTaxDocument","creditCardHolderPhone","creditCardHolderBirthDate"]);
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
                init: function(element, valueAccessor, allBindingsAccessor) {
                    var $el = $(element);
                    var options = {
                            dateFormat:"dd/mm/yy",
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
                update: function(element, valueAccessor)   {
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
            });

            this.creditCardHash.subscribe(function (value) {
                creditCardData.creditCardHash = value;
            });

            this.creditCardHolderFullName.subscribe(function (value) {
                creditCardData.creditCardHolderFullName = value;
            });

            this.creditCardHolderTaxDocument.subscribe(function (value) {
                var typeMaskVat =  value.replace(/\D/g, "").length <= 11 ? "000.000.000-009" : "00.000.000/0000-00";
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
                if(value === "VI" || value === "MC" || value === "ELO" || value === "HC" || value === "HI") {
                    $("#moip_magento2_cc_cc_number").mask("0000 0000 0000 0000");
                } 
                if(value === "DN" || value === "AE") {
                    $("#moip_magento2_cc_cc_number").mask("0000 000000 0000");
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

        beforePlaceOrder() {
            this.getHash();
            if (!$(this.formElement).valid()) {
                return;
            } else {
                 this.placeOrder();
            }
        },

        getHash() {
            var cc = new Moip.CreditCard({
                number  : this.creditCardNumber(),
                cvc     : this.creditCardVerificationNumber(),
                expMonth: this.creditCardExpMonth(),
                expYear : this.creditCardExpYear(),
                pubKey  : this.getPublickey()
            });
            if(cc.isValid()){
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

        getInstall() {
            var valor = quote.totals().base_grand_total;
            var type_interest   = window.checkoutConfig.payment[this.getCode()].type_interest
            var info_interest   = window.checkoutConfig.payment[this.getCode()].info_interest;
            var min_installment = window.checkoutConfig.payment[this.getCode()].min_installment;
            var max_installment = window.checkoutConfig.payment[this.getCode()].max_installment;
            
            var json_parcelas = {};
            var count = 0;
            json_parcelas[1] = 
                        {"parcela" : priceUtils.formatPrice(valor, quote.getPriceFormat()),
                         "total_parcelado" : priceUtils.formatPrice(valor, quote.getPriceFormat()),
                         "total_juros" :  0,
                         "juros" : 0
                        };
                
            var max_div = (valor/min_installment);
                max_div = parseInt(max_div);

            if(max_div > max_installment) {
                max_div = max_installment;
            }else{
                if(max_div > 12) {
                    max_div = 12;
                }
            }
            var limite = max_div;

            _.each( info_interest, function( key, value ) {
                if(count <= max_div){
                    value = info_interest[value];
                    if(value > 0){
                    
                        var taxa = value/100;
                        if(type_interest === "compound"){
                            var pw = Math.pow((1 / (1 + taxa)), count);
                            var parcela = (((valor * taxa) * 1) / (1 - pw));
                        } else {
                            var parcela = ((valor*taxa)+valor) / count;
                        }
                        
                        var total_parcelado = parcela*count;
                        
                        var juros = value;
                        if(parcela > 5 && parcela > min_installment){
                            json_parcelas[count] = {
                                "parcela" : priceUtils.formatPrice(parcela, quote.getPriceFormat()),
                                "total_parcelado": priceUtils.formatPrice(total_parcelado, quote.getPriceFormat()),
                                "total_juros" : priceUtils.formatPrice(total_parcelado - valor, quote.getPriceFormat()),
                                "juros" : juros,
                            };
                        }
                    } else {
                        if(valor > 0 && count > 0){
                            json_parcelas[count] = {
                                    "parcela" : priceUtils.formatPrice((valor/count), quote.getPriceFormat()),
                                    "total_parcelado": priceUtils.formatPrice(valor, quote.getPriceFormat()),
                                    "total_juros" :  0,
                                    "juros" : 0,
                                };
                        }
                    }
                }
                count++;    
            });

            _.each( json_parcelas, function( key, value ) {
                if(key > limite){
                    delete json_parcelas[key];
                }
            });
            return json_parcelas;
        },
        
        getInstallments() {
            var temp = _.map(this.getInstall(), function (value, key) {
                if(value["juros"] === 0){
                    var info_interest = "sem juros";
                } else {
                    var info_interest = "com juros total de " + value["total_juros"];
                }
                var inst = key+" x "+ value["parcela"]+" no valor total de " + value["total_parcelado"] + " " + info_interest;
                    return {
                        "value": key,
                        "installments": inst
                    };
            
                });
            var newArray = [];
            for (var i = 0; i < temp.length; i++) {
                
                if (temp[i].installments!="undefined" && temp[i].installments!=undefined) {
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