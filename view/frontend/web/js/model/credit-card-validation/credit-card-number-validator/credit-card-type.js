/*jshint browser:true jquery:true*/
/*global alert*/
define(
    [
        'jquery',
        'mageUtils'
    ],
    function ($, utils) {
        'use strict';
        var types = [
			
            
            {
                title: 'Visa',
                type: 'visa',
                pattern: '^4\\d*$',
                gaps: [4, 8, 12],
                lengths: [16],
                code: {
                    name: 'CVV',
                    size: 3
                }
            },
            {
                title: 'Mastercard',
                type: 'mastercard',
                pattern: '^5([1-5]\\d*)?$',
                gaps: [4, 8, 12],
                lengths: [16],
                code: {
                    name: 'CVC',
                    size: 3
                }
            },
            {
                title: 'American Express',
                type: 'amex',
                pattern: '^3([47]\\d*)?$',
                isAmex: true,
                gaps: [4, 10],
                lengths: [15],
                code: {
                    name: 'CID',
                    size: 4
                }
            },
            
			{
                title: 'Hipercard',
                type: 'hipercard',
                pattern: '^(606282|3841)[0-9]{5,}$',
                gaps: [4, 8, 12],
                lengths: [13,16,19],
                code: {
                    name: 'CVV',
                    size: 3
                }
            },
            {
                title: 'Elo',
                type: 'elo',
                pattern: '^(636368|438935|504175|451416|636297|5067|4576|4011|50904|50905|50906|65)',
                gaps: [4, 6, 8,10,12,14,15],
                lengths: [16],
                code: {
                    name: 'CVV',
                    size: 3
                }
            },
			{
                title: 'HIPER',
                type: 'hiper',
                pattern: '^(637095|637612|637599|637609|637568)',
                gaps: [4, 8, 12],
                lengths: [12, 13, 14, 15, 16, 17, 18, 19],
                code: {
                    name: 'CVV',
                    size: 3
                }
            }
        ];
        return {
            getCardTypes: function (cardNumber) {
                var i, value,
                    result = [];
                if (utils.isEmpty(cardNumber)) {
                    return result;
                }

                if (cardNumber === '') {
                    return $.extend(true, {}, types);
                }

                for (i = 0; i < types.length; i++) {
                    value = types[i];
                    if (new RegExp(value.pattern).test(cardNumber)) {
						
                        result.push($.extend(true, {}, value));
                    }
                }
                return result;
            }
        }
    }
);
