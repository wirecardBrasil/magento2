/**
 * Copyright © Moip by PagSeguro. All rights reserved.
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */
define([
    "underscore",
    "jquery",
    "Magento_Payment/js/view/payment/cc-form",
    "Moip_Magento2/js/view/payment/lib/jquery/jquery.mask",
    "Magento_Payment/js/model/credit-card-validation/credit-card-data"
], function (_, $, Component, mask, boletoData) {
    "use strict";

    return Component.extend({
        defaults: {
            active: false,
            template: "Moip_Magento2/payment/boleto",
            boletoForm: "Moip_Magento2/payment/boleto-form",
            boletoData: null,
            payerFullName: "",
            payerTaxDocument: ""
        },

        initObservable() {
            this._super().observe(["active", "boletoData", "payerFullName", "payerTaxDocument"]);
            return this;
        },

        getCode() {
            return "moip_magento2_boleto";
        },

        initialize() {
            var self = this;
            var vat = $("#moip_magento2_boleto_payer_tax_document");
            this._super();

            this.payerFullName.subscribe(function (value) {
                boletoData.payerFullName = value;
            });

            this.payerTaxDocument.subscribe(function (value) {
                var typeMaskVat = value.replace(/\D/g, "").length <= 11 ? "000.000.000-009" : "00.000.000/0000-00";
                vat.mask(typeMaskVat, { clearIfNotMatch: true });
                boletoData.payerTaxDocument = value;
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
            if (!$(this.formElement).valid()) {
                return;
            } else {
                this.placeOrder();
            }
        },


        getData() {
            return {
                method: this.getCode(),
                "additional_data": {
                    "boleto_payer_fullname": this.payerFullName(),
                    "boleto_payer_tax_document": this.payerTaxDocument()
                }
            };
        },

        getTitle() {
            return window.checkoutConfig.payment[this.getCode()].title;
        },

        getLogo() {
            return window.checkoutConfig.payment[this.getCode()].logo;
        },

        isShowLegend() {
            return true;
        },

        NameCapture() {
            return window.checkoutConfig.payment[this.getCode()].name_capture;
        },

        TaxDocumentCapture() {
            return window.checkoutConfig.payment[this.getCode()].tax_document_capture;
        },

        getInstructionCheckout() {
            return window.checkoutConfig.payment[this.getCode()].instruction_checkout;
        },

        getExpiration() {
            return window.checkoutConfig.payment[this.getCode()].expiration;
        },

    });
}
);