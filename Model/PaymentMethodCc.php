<?php
namespace Moip\Magento2\Model;

use Magento\Framework\UrlInterface;
use \Magento\Payment\Model\Method\AbstractMethod;
use Magento\Sales\Model\Order;
use \Magento\Framework\Exception\LocalizedException;
use \Magento\Sales\Model\Order\Payment;
use Magento\Quote\Api\Data\PaymentInterface;
use Moip\Moip;
use Moip\Auth\BasicAuth;


class PaymentMethodCc extends \Magento\Payment\Model\Method\Cc
{
	const ROUND_UP 								= 100;
	protected $_canAuthorize 					= true;
	protected $_canCapture 						= true;
	protected $_canRefund 						= true;
    protected $_code 							= 'moipcc';
    protected $_isGateway               		= true;
    protected $_canCapturePartial       		= true;
    protected $_canRefundInvoicePartial 		= true;
	protected $_canVoid                			= true;
	protected $_canCancel              			= true;
	protected $_canReviewPayment 				= false;
	protected $_canUseForMultishipping 			= false;
	protected $_isInitializeNeeded 				= false;
    protected $_countryFactory;
    protected $_supportedCurrencyCodes 			= ['BRL'];
    protected $_debugReplacePrivateDataKeys 	= ['number', 'exp_month', 'exp_year', 'cvc','hash'];
	protected $_cart;
	protected $_moipHelper;
	protected $_canUseInternal          		= false;
	protected $_canFetchTransactionInfo 		= true;
	
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
		$additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);
			if (!is_array($additionalData)) {
				return $this;
		}
		$infoInstance = $this->getInfoInstance();
		$currentData = $data->getAdditionalData();
		foreach($currentData as $key=>$value){
			if ($key === \Magento\Framework\Api\ExtensibleDataInterface::EXTENSION_ATTRIBUTES_KEY) {
				continue;
			}
			$infoInstance->setAdditionalInformation($key,$value);
		}
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
		
		try{
			
			if ($amount <= 0) {
                throw new LocalizedException(__('Invalid amount for authorization.'));
            }
			
			$moip = $this->_moipHelper->AuthorizationValidate();
			$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
			
			$customerMoip = $this->_moipHelper->generateCustomerMoip($order);
			$this->_logger->debug(print_r($customerMoip, true));
			
			try {
				
				
				

					$items 				= $this->_cart->getQuote()->getAllItems();
					$InfoInstance 		= $this->getInfoInstance();
					$moipOrder 			= $this->_moipHelper->initOrderMoip($moip, $order);
					$itemsMoip 			= $this->_moipHelper->addProductItemsMoip($moipOrder, $items);
					$shippingPriceMoip 	= $this->_moipHelper->addShippingPriceMoip($moipOrder, $order);
					$discountPriceMoip 	= $this->_moipHelper->addDiscountPriceMoip($moipOrder, $order);

					$installments 		= $InfoInstance->getAdditionalInformation('installments');

					$additionalPrice	= $this->_moipHelper->addAdditionalPriceMoip($moipOrder, $order, $installments);
				
					
					$moipOrder->setCustomer($customerMoip);
					$moipOrder->create();
					$this->_logger->debug(print_r($moipOrder, true));

					$payMoip =  $this->_moipHelper->addPayCcMoip($moipOrder, $order, $InfoInstance, $payment);
					$this->_logger->debug(print_r($payMoip, true));

					$data_payment = [
											'customer_id'=>$moipOrder->getCustomer()->getId(),
											'ownId'=>$moipOrder->getOwnId(),
											'installments'=> $payMoip->getInstallmentCount(),
											'payid' =>  $payMoip->getId(),
											'Pay' => json_encode($payMoip),
											'Order' => json_encode($moipOrder),
											'hash' => $InfoInstance->getAdditionalInformation('hash')
									];
					
					$payment->setTransactionId($moipOrder->getId())
							->setIsTransactionClosed(0)
							->setIsTransactionPending(1)
							->setTransactionAdditionalInfo('raw_details_info',$data_payment);
				}catch(\Exception $e) {
		            throw new LocalizedException(__('Erro na criação do pagamento ' . $e->getMessage()));
		        }
			} catch(\Exception $e) {
            	throw new LocalizedException(__('Erro na criação da order ' . $e->getMessage()));
        	}
        return $this;
    }
	
	public function fetchTransactionInfo(\Magento\Payment\Model\InfoInterface $payment, $transactionId, $comment = null)
    {	
    	$stateMoip = $this->_moipHelper->getStateOrderMoip($transactionId);
    
		
		if($stateMoip == "PAID"){
			$payment->setIsTransactionApproved(true)->save();
    		$payment->capture(null)->save();
		} elseif($stateMoip == "NOT_PAID"){

			$payment->setIsTransactionDenied(true)->save();
			$order = $payment->getOrder();
			if($comment){
				$order->registerCancellation($comment)->save();
			} else {
				$order->registerCancellation('Pagamento não autorizado')->save();
			}
			

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
		return true;
	}

	
}
