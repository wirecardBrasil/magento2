/*jshint browser:true jquery:true*/
/*global alert*/
(function (factory) {
    if (typeof define === 'function' && define.amd) {
        define([
            'jquery',
            'Magento_Payment/js/model/credit-card-validation/cvv-validator',
            'Moip_Magento2/js/model/credit-card-validation/credit-card-number-validator',
            'Magento_Payment/js/model/credit-card-validation/expiration-date-validator/expiration-year-validator',
            'Magento_Payment/js/model/credit-card-validation/expiration-date-validator/expiration-month-validator',
            'Magento_Payment/js/model/credit-card-validation/credit-card-data',
			'mage/translate'
        ], factory);
    } else {
        factory(jQuery);
    }
}(function ($, cvvValidator, creditCardNumberValidator, expirationDateValidator, monthValidator, creditCardData) {
    "use strict";
	 var creditCartTypes = {
        'visa': [new RegExp('^4[0-9]{12}([0-9]{3})?$'), new RegExp('^[0-9]{3}$'), true],
        'mastercard': [new RegExp('^5([1-5]\\d*)?$'), new RegExp('^[0-9]{3}$'), true],
        'amex': [new RegExp('^3([47]\\d*)?$'), new RegExp('^[0-9]{4}$'), true],
        'elo': [new RegExp('^(636368|438935|504175|451416|636297|5067|4576|4011|50904|50905|50906|65)'), true],
        'hipercard': [new RegExp('^(606282|3841)[0-9]{5,}$'), new RegExp('^[0-9]{3}$'), true],
		'hiper': [new RegExp('^(637095|637612|637599|637609|637568)'), true]
    };
    $.each({
        'validate-cc-type2': [
            function (value, element, params) {
				
				if (value && params) {
                    var ccType = $(params).val();
					
                    value = value.replace(/\s/g, '').replace(/\-/g, '');
                    if (creditCartTypes[ccType] && creditCartTypes[ccType][0]) {
                        return creditCartTypes[ccType][0].test(value);
                    } else if (creditCartTypes[ccType] && !creditCartTypes[ccType][0]) {
                        return true;
                    }
                }
                return false; 
            },
			$.mage.__('Credit card number does not match credit card type.')
        ],
		'validate-card-type2': [
            function (number, item, allowedTypes) {
                var cardInfo,
                    i,
                    l;

                if (!creditCardNumberValidator(number).isValid) {
                    return false;
                } else {
                    cardInfo = creditCardNumberValidator(number).card;

                    for (i = 0, l = allowedTypes.length; i < l; i++) {
				
                        if (cardInfo.title == allowedTypes[i].type) {
                            return true;
                        }
                    }
                    return false;
                }
            },
			$.mage.__('Please enter a valid credit card type number.')
        ],
		'validate-card-number2': [
            /**
             * Validate credit card number based on mod 10
             * @param number - credit card number
             * @return {boolean}
             */
                function (number) {
                return creditCardNumberValidator(number).isValid;
            },
			$.mage.__('Please enter a valid credit card number.')
        ],
		'validate-card-cvv2': [
            /**
             * Validate credit card number based on mod 10
             * @param cvv - month
             * @return {boolean}
             */
                function (cvv) {
                var maxLength = creditCardData.creditCard ? creditCardData.creditCard.code.size : 3;
                return cvvValidator(cvv, maxLength).isValid;
            },
            $.mage.__('Please enter a valid credit card verification number.')
        ],
		'validate-cpf': [
                function (cpf) {
				var digits = cpf.replace(/[\D]/g, '')
				  , dv1, dv2, sum, mod;
				
				if (digits.length == 11) {
				 var d = digits.split('');

				  sum = d[0] * 10 + d[1] * 9 + d[2] * 8 + d[3] * 7 + d[4] * 6 + d[5] * 5 + d[6] * 4 + d[7] * 3 + d[8] * 2;
				  mod = sum % 11;
				  dv1 = (11 - mod < 10 ? 11 - mod : 0);

				  sum = d[0] * 11 + d[1] * 10 + d[2] * 9 + d[3] * 8 + d[4] * 7 + d[5] * 6 + d[6] * 5 + d[7] * 4 + d[8] * 3 + dv1 * 2;
				  mod = sum % 11;
				  dv2 = (11 - mod < 10 ? 11 - mod : 0);
					
				  return dv1 == d[9] && dv2 == d[10];
				}

				return false;
            },
            $.mage.__('The supplied CPF is invalid')
        ]
    }, function (i, rule) {
        rule.unshift(i);
        $.validator.addMethod.apply($.validator, rule);
    });
}));