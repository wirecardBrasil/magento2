/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    "underscore",
    "jquery",
    "Magento_Vault/js/view/payment/method-renderer/vault",
    "Magento_Checkout/js/model/quote",
    "Magento_Catalog/js/price-utils",
    "mage/translate",
    "ko"
], function (_, $, VaultComponent, quote, priceUtils, $t, ko) {
    "use strict";

    return VaultComponent.extend({
        defaults: {
            active: false,
            template: "Moip_Magento2/payment/vault",
            vaultForm: "Moip_Magento2/payment/vault-form"
        },

        initialize() {
            var self = this;
            
            this._super();
        },

        initObservable() {
            this._super().observe(["active"]);
            return this;
        },

        isShowLegend() {
            return true;
        },

        getData() {
            var data = {
                'method': this.getCode(),
                "additional_data": {
                    "cc_cid": $("#"+ this.getId() + '_cc_cid').val(),
                    "cc_installments": $("#"+ this.getId() + '_installments').val(),
                    'public_hash': this.getToken()
                }
            };
           
            return data;
        },

        beforePlaceOrder() {
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

        getInstall() {
            var valor = quote.totals().base_grand_total;
            var type_interest   = window.checkoutConfig.payment[this.getAuxiliaryCode()].type_interest
            var info_interest   = window.checkoutConfig.payment[this.getAuxiliaryCode()].info_interest;
            var min_installment = window.checkoutConfig.payment[this.getAuxiliaryCode()].min_installment;
            var max_installment = window.checkoutConfig.payment[this.getAuxiliaryCode()].max_installment;
            
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
        }
    });
});