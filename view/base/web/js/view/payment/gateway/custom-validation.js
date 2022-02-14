/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/* @api */
define([
    "jquery",
    "Magento_Payment/js/model/credit-card-validation/cvv-validator",
    "Magento_Payment/js/model/credit-card-validation/credit-card-number-validator",
    "Magento_Payment/js/model/credit-card-validation/expiration-date-validator/expiration-year-validator",
    "Magento_Payment/js/model/credit-card-validation/expiration-date-validator/expiration-month-validator",
    "Magento_Payment/js/model/credit-card-validation/credit-card-data",
    "mage/translate"
], function ($, cvvValidator, creditCardNumberValidator, yearValidator, monthValidator, creditCardData) {
    "use strict";

    $(".payment-method-content input[type='tel']").on("keyup", function () {
        if ($(this).val() < 0) {
            $(this).val($(this).val().replace(/^-/, ""));
        }
    });

    var creditCartTypes = {
        "VI": [new RegExp("^4[0-9]{12}([0-9]{3})?$"), new RegExp("^[0-9]{3}$"), true],
        "MC": [
            new RegExp("^(?:5[1-5][0-9]{2}|222[1-9]|22[3-9][0-9]|2[3-6][0-9]{2}|27[01][0-9]|2720)[0-9]{12}$"),
            new RegExp("^[0-9]{3}$"),
            true
        ],
        "AE": [new RegExp("^3[47][0-9]{13}$"), new RegExp("^[0-9]{4}$"), true],
        "DN": [new RegExp("^(3(0[0-5]|095|6|[8-9]))\\d*$"), new RegExp("^[0-9]{3}$"), true],
        "HI": [new RegExp("^((606282)|(637095)|(637568)|(637599)|(637609)|(637612))\\d*$"), new RegExp("^[0-9]{6}$"), true],
        "HC": [new RegExp("^((606282|3841))\\d*$"), new RegExp("^[0-9]{5}$"), true],
        "ELO": [new RegExp("^((509091)|(636368)|(636297)|(504175)|(438935)|(40117[8-9])|(45763[1-2])|" +
            "(457393)|(431274)|(50990[0-2])|(5099[7-9][0-9])|(50996[4-9])|(509[1-8][0-9][0-9])|" +
            "(5090(0[0-2]|0[4-9]|1[2-9]|[24589][0-9]|3[1-9]|6[0-46-9]|7[0-24-9]))|" +
            "(5067(0[0-24-8]|1[0-24-9]|2[014-9]|3[0-379]|4[0-9]|5[0-3]|6[0-5]|7[0-8]))|" +
            "(6504(0[5-9]|1[0-9]|2[0-9]|3[0-9]))|" +
            "(6504(8[5-9]|9[0-9])|6505(0[0-9]|1[0-9]|2[0-9]|3[0-8]))|" +
            "(6505(4[1-9]|5[0-9]|6[0-9]|7[0-9]|8[0-9]|9[0-8]))|" +
            "(6507(0[0-9]|1[0-8]))|(65072[0-7])|(6509(0[1-9]|1[0-9]|20))|" +
            "(6516(5[2-9]|6[0-9]|7[0-9]))|(6550(0[0-9]|1[0-9]))|" +
            "(6550(2[1-9]|3[0-9]|4[0-9]|5[0-8])))\\d*$"), true]
    };

    $.each({

        "validate-cc-type-moip": [

            /**
             * Validate credit card number is for the correct credit card type.
             *
             * @param {String} value - credit card number
             * @param {*} element - element contains credit card number
             * @param {*} params - selector for credit card type
             * @return {Boolean}
             */
            function (value, element, params) {
                var ccType;

                if (value && params) {
                    ccType = $(params).val();
                    value = value.replace(/\s/g, "").replace(/\-/g, "");
                    if (creditCartTypes[ccType] && creditCartTypes[ccType][0]) {
                        return creditCartTypes[ccType][0].test(value);
                    } else if (creditCartTypes[ccType] && !creditCartTypes[ccType][0]) {
                        return true;
                    }
                }

                return false;
            },
            $.mage.__("Credit card number does not match credit card type.")
        ],
        "validate-card-type-moip": [

            /**
             * Validate credit type moip is for the correct credit card type.
             *
             * @param {String} value - credit card number
             * @param {*} element - element contains credit card number
             * @param {*} params - selector for credit card type
             * @return {Boolean}
             */
            function (number, item, allowedTypes) {
                var cardInfo,
                    i,
                    l;

                if (!creditCardNumberValidator(number).isValid) {
                    return false;
                }

                cardInfo = creditCardNumberValidator(number).card;

                for (i = 0, l = allowedTypes.length; i < l; i++) {
                    if (cardInfo.title == allowedTypes[i].type) { //eslint-disable-line eqeqeq
                        return true;
                    }
                }

                return false;
            },
            $.mage.__("Please enter a valid credit card type number.")
        ],
        "validate-card-number-moip": [

            /**
             * Validate credit card number based on mod 10
             *
             * @param {*} number - credit card number
             * @return {Boolean}
             */
            function (number) {
                return creditCardNumberValidator(number).isValid;
            },
            $.mage.__("Please enter a valid credit card number.")
        ],
        "validate-card-date-moip": [

            /**
             * Validate credit card expiration month
             *
             * @param {String} date - month
             * @return {Boolean}
             */
            function (date) {
                return monthValidator(date).isValid;
            },
            $.mage.__("Incorrect credit card expiration month.")
        ],
        "validate-card-cvv-moip": [

            /**
             * Validate cvv
             *
             * @param {String} cvv - card verification value
             * @return {Boolean}
             */
            function (cvv) {
                var maxLength = creditCardData.creditCard ? creditCardData.creditCard.code.size : 3;

                return cvvValidator(cvv, maxLength).isValid;
            },
            $.mage.__("Please enter a valid credit card verification number.")
        ],
        "validate-card-year-moip": [

            /**
             * Validate credit card expiration year
             *
             * @param {String} date - year
             * @return {Boolean}
             */
            function (date) {
                return yearValidator(date).isValid;
            },
            $.mage.__("Incorrect credit card expiration year.")
        ]

    }, function (i, rule) {
        rule.unshift(i);
        $.validator.addMethod.apply($.validator, rule);
    });
});
