/*browser:true*/
/*global define*/
define(
[
    'underscore',
	'jquery',
	'ko',
	'Magento_Checkout/js/model/quote',
	'Magento_Catalog/js/price-utils',
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/action/place-order',
    'Magento_Checkout/js/action/select-payment-method',
    'Magento_Customer/js/model/customer',
    'Magento_Checkout/js/checkout-data',
	'Magento_Payment/js/model/credit-card-validation/credit-card-data',
	'Magento_Payment/js/model/credit-card-validation/validator',
    'Magento_Checkout/js/model/payment/additional-validators',
	'Moip_Magento2/js/model/credit-card-validation/credit-card-number-validator',
	'Moip_Magento2/js/model/credit-card-validation/custom',
    'mage/url',
	'mage/calendar',
	'mage/translate'
],
function (
    _,
	$,
	ko,
	quote,
	priceUtils,
    Component,
    placeOrderAction,
    selectPaymentMethodAction,
    customer,
    checkoutData,
	creditCardData,
	validator,
    additionalValidators,
	cardNumberValidator,
	custom,
    url,
	calendar) {
        'use strict';
		
        return Component.extend({
            defaults: {
                template: 'Moip_Magento2/payment/cc',
			    creditCardType: '',
                creditCardExpYear: '',
                creditCardExpMonth: '',
                creditCardNumber: '',
                creditCardSsStartMonth: '',
                creditCardSsStartYear: '',
                creditCardSsIssue: '',
                creditCardVerificationNumber: '',
                selectedCardType: null
            },
			
			getCode: function() {
                return 'moipcc';
            },
		
			initObservable: function () {
                this._super()
                    .observe([
                        'creditCardType',
                        'creditCardExpYear',
                        'creditCardExpMonth',
                        'creditCardNumber',
                        'creditCardVerificationNumber',
                        'creditCardSsStartMonth',
                        'creditCardSsStartYear',
                        'creditCardSsIssue',
                        'selectedCardType'
                    ]);

                return this;
            },
			 
			 initialize: function () {
				this._super();
		
			  var self = this;
			  //Set credit card number to credit card data object
                this.creditCardNumber.subscribe(function (value) {
                    var result;
					
                    self.selectedCardType(null);

                    if (value === '' || value === null) {
                        return false;
                    }
                    result = cardNumberValidator(value);
					
                    if (!result.isPotentiallyValid && !result.isValid) {
                        return false;
                    }

                    if (result.card !== null) {
                        self.selectedCardType(result.card.type);
                        creditCardData.creditCard = result.card;
                    }

                    if (result.isValid) {
                        creditCardData.creditCardNumber = value;
                        self.creditCardType(result.card.type);
                    }
                });
				
				 //Set expiration year to credit card data object
                this.creditCardExpYear.subscribe(function (value) {
                    creditCardData.expirationYear = value;
                });

                //Set expiration month to credit card data object
                this.creditCardExpMonth.subscribe(function (value) {
                    creditCardData.expirationMonth = value;
                });

                //Set cvv code to credit card data object
                this.creditCardVerificationNumber.subscribe(function (value) {
                    creditCardData.cvvCode = value;
                });
        
				
			},
			getCvvImageUrl: function () {
	            return window.checkoutConfig.payment.moipcc.image_cvv;
	        },

	        getCvvImageHtml: function () {
	            return '<img src="' + this.getCvvImageUrl() +
	                '" alt="Referencia visual do CVV" title="Referencia visual do CVV" />';
	        },
			getCcAvailableTypes: function() {
                return window.checkoutConfig.payment.this.item.method.ccavailableTypes;
            },
            
            selectPaymentMethod: function() {
                selectPaymentMethodAction(this.getData());
                checkoutData.setSelectedPaymentMethod(this.item.method);
                return true;
            },
            
            getPublickey: function() {
				
                return window.checkoutConfig.payment.moipcc.publickey;
            },

			getIcons: function (type) {
                return window.checkoutConfig.payment.moipcc.icons.hasOwnProperty(type) ?
                    window.checkoutConfig.payment.moipcc.icons[type]
                    : false;
            },
			getCcAvailableTypesValues: function () {
				
                return _.map(window.checkoutConfig.payment.moipcc.ccavailabletypes, function (value, key) {
                    return {
                        'value': key,
                        'type': value
                    };
                });  
            },
			getCcYearsValues: function () {
                return _.map(window.checkoutConfig.payment.moipcc.years, function (value, key) {
                    return {
                        'value': key,
                        'year': value
                    };
                });
            },
			getCcMonthsValues: function () {
                return _.map(window.checkoutConfig.payment.moipcc.months, function (value, key) {
                    return {
                        'value': key,
                        'month': value
                    };
                });
            },
            getUseDocument: function(){
            	console.log(window.checkoutConfig.payment.moipcc.get_document);
            	return window.checkoutConfig.payment.moipcc.get_document;
            },
			isActive :function(){
				return true;
			},
			
			getInstallmentsActive: ko.computed(function () {
			   return 1;
            }),
			
			getInstall: function () {
				var valor = quote.totals().base_grand_total;
				//console.log(valor);
				var type_interest 	= window.checkoutConfig.payment.moipcc.type_interest
				var info_interest 	= window.checkoutConfig.payment.moipcc.info_interest;
				var min_installment = window.checkoutConfig.payment.moipcc.min_installment;
				var max_installment = window.checkoutConfig.payment.moipcc.max_installment;
				
				var json_parcelas = {};
				var count = 0;
			    json_parcelas[1] = 
							{'parcela' : priceUtils.formatPrice(valor, quote.getPriceFormat()),
                             'total_parcelado' : priceUtils.formatPrice(valor, quote.getPriceFormat()),
                             'total_juros' :  0,
                             'juros' : 0
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
							if(type_interest == "compound"){
								var pw = Math.pow((1 / (1 + taxa)), count);
								var parcela = ((valor * taxa) / (1 - pw));
							} else {
								var parcela = ((valor*taxa)+valor) / count;
							}
							
							var total_parcelado = parcela*count;
							
							var juros = value;
							if(parcela > 5 && parcela > min_installment){
								json_parcelas[count] = {
									'parcela' : priceUtils.formatPrice(parcela, quote.getPriceFormat()),
									'total_parcelado': priceUtils.formatPrice(total_parcelado, quote.getPriceFormat()),
									'total_juros' : priceUtils.formatPrice(total_parcelado - valor, quote.getPriceFormat()),
									'juros' : juros,
								};
							}
						} else {
							if(valor > 0 && count > 0){
								json_parcelas[count] = {
										'parcela' : priceUtils.formatPrice((valor/count), quote.getPriceFormat()),
										'total_parcelado': priceUtils.formatPrice(valor, quote.getPriceFormat()),
										'total_juros' :  0,
										'juros' : 0,
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
			
			getInstallments: function () {
			var temp = _.map(this.getInstall(), function (value, key) {
				if(value['juros'] == 0){
					var info_interest = "sem juros";
				} else {
					var info_interest = "com juros total de " + value['total_juros'];
				}
				var inst = key+' x '+ value['parcela']+' no valor total de ' + value['total_parcelado'] + ' ' + info_interest;
                    return {
                        'value': key,
                        'installments': inst
                    };
			
                });
			var newArray = [];
			for (var i = 0; i < temp.length; i++) {
				
				if (temp[i].installments!='undefined' && temp[i].installments!=undefined) {
					newArray.push(temp[i]);
				}
			}
			
			return newArray;
			},

			getHash: function(){
				var cc = new Moip.CreditCard({
		            number  : this.creditCardNumber(),
		            cvc     : this.creditCardVerificationNumber(),
		            expMonth: this.creditCardExpMonth(),
		            expYear : this.creditCardExpYear(),
		            pubKey  : this.getPublickey()
		          });
		          /*console.log(cc);*/
		          if( cc.isValid()){
		          	jQuery('#'+this.getCode()+'_hash').val(cc.hash());
		            /*console.log(cc.hash());*/
		          }
		          else{
		           /* console.log('Invalid credit card. Verify parameters: number, cvc, expiration Month, expiration Year');*/
		          }
		         return cc;
			},

            getData: function () {
                return {
                    'method': this.item.method,
                    'additional_data': {
                        'cc_number': this.creditCardNumber(),
                        'cc_type': this.creditCardType(),
						'cc_exp_month': this.creditCardExpMonth(),
						'cc_exp_year': this.creditCardExpYear(),
						'fullname': jQuery('#'+this.getCode()+'_fullname').val(),
						'document': jQuery('#'+this.getCode()+'_document').val(),
						'installments': jQuery('#'+this.getCode()+'_installments').val(),
						'hash': jQuery('#'+this.getCode()+'_hash').val()
                    }
                };
            },
		
			validate: function() {
				var $form = $('#' + this.getCode() + '-form');
				this.getHash();
                return $form.validation() && $form.validation('isValid');
            }
        });
    }
);
