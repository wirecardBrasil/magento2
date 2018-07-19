<?php
namespace Moip\Magento2\Model;

use Magento\Framework\UrlInterface;
use \Magento\Payment\Model\Method\AbstractMethod;
use Magento\Sales\Model\Order;
use \Magento\Framework\Exception\LocalizedException;
use \Magento\Sales\Model\Order\Payment;
use Moip\Moip;
use Moip\Auth\BasicAuth;


class PaymentMethodCc extends \Magento\Payment\Model\Method\Cc
{
	const ROUND_UP = 100;
	protected $_canAuthorize = true;
	protected $_canCapture = true;
	protected $_canRefund = true;
    protected $_code = 'moipcc';
    protected $_isGateway               = true;
    protected $_canCapturePartial       = true;
    protected $_canRefundInvoicePartial = true;
	protected $_canVoid                = true;
	protected $_canCancel              = true;
	protected $_canUseForMultishipping = false;
	/*protected $_isInitializeNeeded 	= true;*/
    protected $_countryFactory;
    protected $_supportedCurrencyCodes = ['BRL'];
    protected $_debugReplacePrivateDataKeys = ['number', 'exp_month', 'exp_year', 'cvc'];
	protected $_cart;
	protected $_moipHelper;
	
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

	/*public function getConfigPaymentAction()
	{
	    return ($this->getConfigData('order_status') == 'pending')? null : parent::getConfigPaymentAction();
	}*/

	public function assignData(\Magento\Framework\DataObject $data)
	 {
		parent::assignData($data);
		$infoInstance = $this->getInfoInstance();
		$currentData = $data->getAdditionalData();
		foreach($currentData as $key=>$value){
			$infoInstance->setAdditionalInformation($key,$value);
		}
		return $this;
	 }
	
	 public function validate()
    {
		$moip = $this->_moipHelper->AuthorizationValidate();
        return $this;
    } 
	
	


    /**
     * Payment authorize
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Validator\Exception
     */
    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
		//parent::authorize($payment, $amount);  
		$order = $payment->getOrder();
		
		try{
			
			if ($amount <= 0) {
                throw new LocalizedException(__('Invalid amount for authorization.'));
            }
			
			$moip = $this->_moipHelper->AuthorizationValidate();
			$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
			
			$customerMoip = $this->_moipHelper->generateCustomerMoip($order);

			
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
					

					$payMoip =  $this->_moipHelper->addPayCcMoip($moipOrder, $customerMoip, $InfoInstance, $payment);
					

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
							->setTransactionAdditionalInfo('raw_details_info',$data_payment);
				}catch(\Exception $e) {
		            throw new LocalizedException(__('Payment failed ' . $e->getMessage()));
		        }
			} catch(\Exception $e) {
            	throw new LocalizedException(__('Payment failed ' . $e->getMessage()));
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