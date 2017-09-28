<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Moip\Magento2\Block;

use Magento\Customer\Model\Context;
use Magento\Sales\Model\Order;

class Success extends \Magento\Framework\View\Element\Template
{
	/**
     * @var \Magento\Checkout\Model\Session
    */
    protected $_checkoutSession;
    /**
     * @var \Magento\Sales\Model\Order\Config
     */
    protected $_orderConfig;
    /**
     * @var \Magento\Framework\App\Http\Context
     */
    protected $httpContext;

	public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\Order\Config $orderConfig,
        \Magento\Framework\App\Http\Context $httpContext,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_checkoutSession = $checkoutSession;
        $this->_orderConfig = $orderConfig;
        $this->_isScopePrivate = true;
        $this->httpContext = $httpContext;
    }


    public function getPayment(){

    	$order = $this->_checkoutSession->getLastRealOrder();
    	$payment = $order->getPayment()->getMethodInstance();
    	return $payment;

    }
    public function getMethodCode()
    {
    	$method = $this->getPayment()->getCode();
    	
        return  $method;
    }


    public function getInfo($info)
    {
    	$_info = $this->getPayment()->getInfoInstance()->getAdditionalInformation($info);
    	
        return  $_info;
    }
}