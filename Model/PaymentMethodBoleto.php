<?php
namespace Moip\Magento2\Model;

use Magento\Framework\UrlInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObject;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Sales\Model\Order;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order\Payment;
use Moip\Moip;
use Moip\Auth\BasicAuth;


class PaymentMethodBoleto extends \Magento\Payment\Model\Method\Cc
{
	const ROUND_UP = 100;
	protected $_canAuthorize 				= false;
	protected $_canCapture 					= true;
	protected $_canRefund 					= true;
    protected $_code 						= 'moipboleto';
    protected $_isGateway               	= true;
    protected $_canCapturePartial       	= true;
    protected $_canRefundInvoicePartial 	= true;
	protected $_canVoid                		= true;
	protected $_canCancel              		= true;
	protected $_canUseForMultishipping 		= false;
	protected $_canReviewPayment 			= true;
    protected $_countryFactory;
    protected $_supportedCurrencyCodes 		= ['BRL'];
    protected $_canUseInternal          	= false;
	protected $_cart;
	protected $_moipHelper;
	protected $_infoBlockType 				= 'Moip\Magento2\Block\Info\Boleto';
	protected $_canFetchTransactionInfo 	= true;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Directory\Model\CountryFactory $countryFactory,
		\Magento\Checkout\Model\Cart $cart,
		\Moip\Magento2\Helper\Data $moipHelper,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $moduleList,
            $localeDate,
            null,
            null,
            $data
        );
        $this->_countryFactory = $countryFactory;
        $this->scopeConfig = $scopeConfig;
		$this->_cart = $cart;
		$this->_moipHelper = $moipHelper;
    }
	
	public function assignData(\Magento\Framework\DataObject $data)
	{
		parent::assignData($data);
		return $this;
	}
	
	public function validate()
    {
		$moip = $this->_moipHelper->AuthorizationValidate();
        return $this;
    }

    public function order(\Magento\Payment\Model\InfoInterface $payment, $amount)
    { 
		$order = $payment->getOrder();
		if ($amount <= 0) {
            throw new LocalizedException(__('Invalid amount for authorization.'));
        }
		$moip 				= $this->_moipHelper->AuthorizationValidate();
		$customerMoip 		= $this->_moipHelper->generateCustomerMoip($order);
		$items 				= $this->_cart->getQuote()->getAllItems();
		$moipOrder 			= $this->_moipHelper->initOrderMoip($moip, $order);
		$itemsMoip 			= $this->_moipHelper->addProductItemsMoip($moipOrder, $items);
		$shippingPriceMoip 	= $this->_moipHelper->addShippingPriceMoip($moipOrder, $order);
		$discountPriceMoip 	= $this->_moipHelper->addDiscountPriceMoip($moipOrder, $order);
		$additionalPrice	= $this->_moipHelper->addAdditionalPriceMoip($moipOrder, $order);
		$moipOrder->setCustomer($customerMoip);
		try {
			$moipOrder->create();
		} catch(\Exception $e) {
			$this->_logger->debug(print_r($moipOrder, true));
			throw new LocalizedException(__('Erro na criação do pagamento ' . $e->getMessage()));
		}
	    try {
			$payMoip 			= $this->_moipHelper->addPayBoletoMoip($moipOrder);
		} catch(\Exception $e) {
			$this->_logger->debug(print_r($moipOrder, true));
            throw new LocalizedException(__('Erro na criação do pagamento ' . $e->getMessage()));
        }

			
		$data_payment = [
						'customer_id'=>$moipOrder->getCustomer()->getId(),
						'ownId'=>$moipOrder->getOwnId(),
						'href_boleto'=> $payMoip->getHrefBoleto(),
						'href_boleto_print'=> $payMoip->getHrefPrintBoleto(),
						'line_code_boleto'	=> $payMoip->getLineCodeBoleto(),
						'expiration_date_boleto' => $payMoip->getExpirationDateBoleto(),
						'payid' =>  $payMoip->getId(),
						'Pay' => json_encode($payMoip),
						'Order' => json_encode($moipOrder)
					];
		$payment->setTransactionId($moipOrder->getId())
				->setIsTransactionClosed(1)
				->setIsTransactionPending(1)
				->setTransactionAdditionalInfo('raw_details_info', $data_payment);

		$this->getInfoInstance()->setAdditionalInformation($data_payment);		
        return $this;
    }
	
  	public function denyPayment(\Magento\Payment\Model\InfoInterface $payment)
    {    
        $payment->setIsTransactionDenied(true)->save();
        return $this; 
    }

    public function acceptPayment(\Magento\Payment\Model\InfoInterface $payment)
    {
        $payment->setIsTransactionApproved(true)->save();
    	$payment->capture(null)->save();
        return $this;
    }
   
    public function fetchTransactionInfo(\Magento\Payment\Model\InfoInterface $payment, $transactionId)
    {	
    	$stateMoip = $this->_moipHelper->getStateOrderMoip($transactionId);
		if($stateMoip == "PAID"){
			$payment->setIsTransactionApproved(true)->setIsCustomerNotified(true)->setEmailSent(true)->save();
    		$payment->capture(null)->save();
		} elseif($stateMoip == "NOT_PAID"){
			$payment->setIsTransactionDenied(true)->save();
			$order = $payment->getOrder();
			$order->registerCancellation('Pagamento não autorizado. Prazo limite excedido')->save();
		} else {
			parent::fetchTransactionInfo($payment, $transactionId);
		}
        return $this;
    }

    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        if (!$this->isActive($quote ? $quote->getStoreId() : null)) {
            return false;
        }

        $checkResult = new DataObject();
        $checkResult->setData('is_available', true);

        // for future use in observers
        $this->_eventManager->dispatch(
            'payment_method_is_active',
            [
                'result' => $checkResult,
                'method_instance' => $this,
                'quote' => $quote
            ]
        );

        return $checkResult->getData('is_available');
    }
}