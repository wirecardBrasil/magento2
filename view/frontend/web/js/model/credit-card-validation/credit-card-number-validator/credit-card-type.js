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
                title: 'Discover',
                type: 'discover',
                pattern: '^6(?:011|5[0-9]{2})[0-9]{12}$',
                gaps: [4, 8, 12],
                lengths: [16], 
                code: { 
                    name: 'CID',
                    size: 3
                }
            },
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
                title: 'Diners',
                type: 'diners',
                pattern: '^3((0([0-5]\\d*)?)|[689]\\d*)?$',
                gaps: [4, 10],
                lengths: [14],
                code: {
                    name: 'CVV',
                    size: 3
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
                pattern: '^(636368|438935|504175|451416|636297|5067|4576|4011|50904|50905|50906)',
                gaps: [4, 8, 12],
                lengths: [16],
                code: {
                    name: 'CVV',
                    size: 3
                }
            },
            {
                title: 'Aura',
                type: 'aura',
                pattern: '^50[0-9]{14,17}$',
                gaps: [4, 8, 12],
                lengths: [12, 13, 14, 15, 16, 17, 18, 19],
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
