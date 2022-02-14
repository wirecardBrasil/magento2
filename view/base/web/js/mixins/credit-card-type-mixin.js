/**
 * Copyright © Moip by PagSeguro. All rights reserved.
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */
define([
    "jquery",
    "mageUtils"
], function ($, utils) {
    "use strict";
    var typesMoip = [
        {
            title: "Visa",
            type: "VI",
            pattern: "^4\\d*$",
            gaps: [4, 8, 12],
            lengths: [16],
            code: {
                name: "CVV",
                size: 3
            }
        },
        {
            title: "MasterCard",
            type: "MC",
            pattern: "^(?:5[1-5][0-9]{2}|222[1-9]|22[3-9][0-9]|2[3-6][0-9]{2}|27[01][0-9]|2720)[0-9]{12}$",
            gaps: [4, 8, 12],
            lengths: [16],
            code: {
                name: "CVC",
                size: 3
            }
        },
        {
            title: "American Express",
            type: "AE",
            pattern: "^3([47]\\d*)?$",
            isAmex: true,
            gaps: [4, 10],
            lengths: [15],
            code: {
                name: "CID",
                size: 4
            }
        },
        {
            title: "Diners",
            type: "DN",
            pattern: "^(3(0[0-5]|095|6|[8-9]))\\d*$",
            gaps: [4, 10],
            lengths: [14, 16, 17, 18, 19],
            code: {
                name: "CVV",
                size: 3
            }
        },
        {
            title: "Hipercard",
            type: "HC",
            pattern: "^(606282|3841)[0-9]{5,}$",
            gaps: [4, 8, 12],
            lengths: [13, 16],
            code: {
                name: "CVC",
                size: 3
            }
        },
        {
            title: "Hiper",
            type: "HI",
            pattern: "^(637095|637612|637599|637609|637568)",
            gaps: [4, 8, 12],
            lengths: [12, 13, 14, 15, 16, 17, 18, 19],
            code: {
                name: "CVV",
                size: 3
            }
        },
        {
            title: "Elo",
            type: "ELO",
            pattern: "^((509091)|(636368)|(636297)|(504175)|(438935)|(40117[8-9])|(45763[1-2])|" +
                "(457393)|(431274)|(50990[0-2])|(5099[7-9][0-9])|(50996[4-9])|(509[1-8][0-9][0-9])|" +
                "(5090(0[0-2]|0[4-9]|1[2-9]|[24589][0-9]|3[1-9]|6[0-46-9]|7[0-24-9]))|" +
                "(5067(0[0-24-8]|1[0-24-9]|2[014-9]|3[0-379]|4[0-9]|5[0-3]|6[0-5]|7[0-8]))|" +
                "(6504(0[5-9]|1[0-9]|2[0-9]|3[0-9]))|" +
                "(6504(8[5-9]|9[0-9])|6505(0[0-9]|1[0-9]|2[0-9]|3[0-8]))|" +
                "(6505(4[1-9]|5[0-9]|6[0-9]|7[0-9]|8[0-9]|9[0-8]))|" +
                "(6507(0[0-9]|1[0-8]))|(65072[0-7])|(6509(0[1-9]|1[0-9]|20))|" +
                "(6516(5[2-9]|6[0-9]|7[0-9]))|(6550(0[0-9]|1[0-9]))|" +
                "(6550(2[1-9]|3[0-9]|4[0-9]|5[0-8])))\\d*$",
            gaps: [4, 8, 12],
            lengths: [16],
            code: {
                name: "CVC",
                size: 3
            }
        }
    ];

    var mixin = {
        getCardTypes(cardNumber) {
            var i, value,
                result = [];
            if (utils.isEmpty(cardNumber)) {
                return result;
            }
            if (cardNumber === "") {
                return $.extend(true, {}, typesMoip);
            }
            for (i = 0; i < typesMoip.length; i++) {
                value = typesMoip[i];
                if (new RegExp(value.pattern).test(cardNumber)) {
                    result.push($.extend(true, {}, value));
                }
            }
            return result;
        }
    };

    return function (target) {
        return mixin;
    };
});
