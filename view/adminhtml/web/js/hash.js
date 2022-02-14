/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
 define([
	"underscore",
	"jquery"
], function (_, $) {
	"use strict";

	function setHash() {
		var cc = new Moip.CreditCard({
			number  : $("#moip_magento2_cc_cc_number").val(),
			cvc     : $("#moip_magento2_cc_cc_cid").val(),
			expMonth: $("#moip_magento2_cc_expiration").val(),
			expYear : $("#moip_magento2_cc_expiration_yr").val(),
			pubKey  : $("#moip_magento2_cc_key_public").val(),
		});
		if(cc.isValid()){
			$("#moip_magento2_cc_cc_hash").val(cc.hash());
		}
	};

	$("#moip_magento2_cc_cc_installments").change(function() {
		setHash();
	});

	$("#moip_magento2_cc_cc_number").change(function() {
		setHash();
	});

	$("#moip_magento2_cc_cc_cid").change(function() {
		setHash();
	});

	$("#moip_magento2_cc_expiration").change(function() {
		setHash();
	});

	$("#moip_magento2_cc_expiration_yr").change(function() {
		setHash();
	});

});