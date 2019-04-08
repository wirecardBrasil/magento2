<?php 
namespace Moip\Magento2\Helper;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Backend\App\Action;
use Moip\Moip;
use Moip\Auth\OAuth;

class Data extends \Magento\Framework\App\Helper\AbstractHelper {

	
	protected $_scopeConfig;
	protected $tokenauth;
	protected $keyauth;
	protected $_objectManager;
	protected $date;

	const ROUND_UP 					= 100;

	const REDIRECT_URI_SANDBOX 		= "http://loja.moip.com.br/magento2/redirect/";
	const URL_KEY_SANDBOX			= "https://sandbox.moip.com.br/v2/keys/";
	const APP_ID_SANDBOX 			= "APP-CKN5214B60GC";
	const CLIENT_SECRECT_SANDBOX 	= "5be91ec716cb46b8844861237168c8dc";


	const REDIRECT_URI_PRODUCTION	= "http://loja.moip.com.br/magento2/redirect/";
	const URL_KEY_PRODUCTION		= "https://api.moip.com.br/v2/keys/";
	const APP_ID_PRODUCTION			= "APP-ZDVW5HTDKG16";
	const CLIENT_SECRECT_PRODUCTION	= "cb49330579e144fdb40e22b50e04269e";
	
	

	public function __construct(
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Magento\Framework\ObjectManagerInterface $objectManager,
		\Magento\Framework\Stdlib\DateTime\DateTime $date,
		\Magento\Store\Model\StoreManagerInterface $storeManager,
		\Magento\Payment\Model\Method\Logger $logger
   	) {
	   $this->_scopeConfig = $scopeConfig;
	   $this->_objectManager = $objectManager;
	   $this->date = $date;
	   $this->_storeManager = $storeManager;
	   $this->_logger = $logger;
	}
	
	public function AuthorizationValidate() 
	{
		$_environment = $this->getEnvironmentMode();
		
		$_oauth = $this->getOauth($_environment);

		if($_environment === "production"){
			$moip = new Moip(new OAuth($_oauth), Moip::ENDPOINT_PRODUCTION);
		}else{
			$moip = new Moip(new OAuth($_oauth), Moip::ENDPOINT_SANDBOX);
		}
		return $moip;
	}

	public function generateCustomerMoip($order){
		$moip = $this->AuthorizationValidate();


		if (!$order->getCustomerFirstname()) {
				$name = $order->getBillingAddress()->getName();
		} else {
				$name = $order->getCustomerFirstname() . ' ' . $order->getCustomerLastname();
		}

		$type_cpf 	= $this->getTypeForCpf();

		if($type_cpf === "customer"){
			$attribute_cpf_customer = $this->getCpfAttributeForCustomer();
			$_taxvat = $order->getData('customer_'.$attribute_cpf_customer);
		} else {
			$attribute_cpf_address = $this->getCpfAttributeForAddress();
			$_taxvat = $order->getBillingAddress()->getData($attribute_cpf_address);
		}

		$taxvat = preg_replace("/[^0-9]/", "",$_taxvat);

		$type_cnpj 	= $this->getTypeForCNPJ();

		if($type_cnpj === "use_cpf"){

			if(strlen($taxvat) === 14) {
				$_typedocument = "CNPJ";
				$type_name_company = $this->getTypeNameCompany();

				if($type_name_company === "customer"){
					$attribute_name = $this->getCompanyAttributeForCustomer();
					$name 		= $order->getData('customer_'.$attribute_name);
				} else {
					$attribute_name = $this->getCompanyAttributeForAddress();
					$name 		= $order->getBillingAddress()->getData($attribute_name);
				}

			} else {
				$_typedocument = "CPF";
			}

		} elseif ($type_cnpj === "use_customer") {
			$attribute_cnpj = $this->getCNPJAttributeForCustomer();
			$_taxvat 		= $order->getData('customer_'.$attribute_cnpj);
			if($_taxvat){
				$_typedocument = "CNPJ";
				$type_name_company = $this->getTypeNameCompany();
				if($type_name_company === "customer"){
					$attribute_name = $this->getCompanyAttributeForCustomer();
					$name 		= $order->getData('customer_'.$attribute_name);
				} else {
					$attribute_name = $this->getCompanyAttributeForAddress();
					$name 		= $order->getBillingAddress()->getData($attribute_name);
				}

			}
		} elseif($type_cnpj === "use_address"){
			$attribute_cnpj_address = $this->getCNPJAttributeForAddress();
			$_taxvat = $order->getBillingAddress()->getData($attribute_cnpj_address);
			if($_taxvat){
				$_typedocument = "CNPJ";
				$type_name_company = $this->getTypeNameCompany();
				if($type_name_company === "customer"){
					$attribute_name = $this->getCompanyAttributeForCustomer();
					$name 		= $order->getData('customer_'.$attribute_name);
				} else {
					$attribute_name = $this->getCompanyAttributeForAddress();
					$name 		= $order->getBillingAddress()->getData($attribute_name);
				}
			}
		}

		$taxvat = preg_replace("/[^0-9]/", "",$_taxvat);
		
		$email = $order->getCustomerEmail();
		
		$dob = $order->getCustomerDob()
            		? date('Y-m-d', strtotime($order->getCustomerDob()))
            		: '1985-10-10';
		
		$ddd_telephone 		= $this->getNumberOrDDD($order->getBillingAddress()->getTelephone(), true);
		$number_telephone 	= $this->getNumberOrDDD($order->getBillingAddress()->getTelephone(), false);

		$street_billing  	= $order->getBillingAddress()->getStreet();
		
		$city_billing 		= $order->getBillingAddress()->getData('city');
		
		$region_billing 	= $order->getBillingAddress()->getRegionCode();
		
		$postcode_billing 	= substr(preg_replace("/[^0-9]/", "", $order->getBillingAddress()->getData('postcode')) . '00000000', 0, 8);
		
		$billing_logradouro 	= $street_billing[$this->getStreetPositionLogradouro()];

		$billing_number 		= $street_billing[$this->getStreetPositionNumber()];
		
		if(count($street_billing) >= 3){
			$billing_district 		= $street_billing[$this->getStreetPositionDistrict()];
		} else {
			$billing_district 		= $street_billing[$this->getStreetPositionLogradouro()];
		}


		if(count($street_billing) == 4){
			$billing_complemento	= $street_billing[$this->getStreetPositionComplemento()];
		} else {
			$billing_complemento	= "";
		}


		if (!$order->getIsVirtual()) {
			$city_shipping 		= $order->getShippingAddress()->getData('city');
			$street_shipping 	= $order->getShippingAddress()->getStreet();
			$region_shipping 	= $order->getShippingAddress()->getRegionCode();
			$postcode_shipping 	= substr(preg_replace("/[^0-9]/", "", $order->getShippingAddress()->getData('postcode')) . '00000000', 0, 8);

			$shipping_logradouro 	= $street_shipping[$this->getStreetPositionLogradouro()];

			$shipping_number 		= $street_shipping[$this->getStreetPositionNumber()];

			if(count($street_billing) >= 3) {
				$shipping_district 		= $street_shipping[$this->getStreetPositionDistrict()];
			} else {
				$shipping_district 		= $street_shipping[$this->getStreetPositionLogradouro()];
			}
			

			if(count($street_shipping) == 4){
				$shipping_complemento	=  $street_shipping[$this->getStreetPositionComplemento()];
			} else {
				$shipping_complemento	=  "";
			}
		}



		$customer =  $moip->customers()->setOwnId(uniqid())
			        ->setFullname($name)
			        ->setEmail($email)
			        ->setBirthDate($dob)
			        ->setTaxDocument($taxvat, $_typedocument)
			        ->setPhone($ddd_telephone, $number_telephone)
			        	->addAddress('BILLING',
								            $billing_logradouro, 
								            $billing_number,
								            $billing_district, 
								            $city_billing, 
								            $region_billing,
								            $postcode_billing, 
								            $billing_complemento
								     );
		if (!$order->getIsVirtual()) {
			        $customer->addAddress('SHIPPING',
								            $shipping_logradouro, 
								            $billing_number,
								            $shipping_district, 
								            $city_shipping, 
								            $region_shipping,
								            $postcode_shipping, 
								            $shipping_complemento
				            );
		}
		try{
			if($taxvat != ""){
				$customer = $customer->create();
			}
		} catch(\Exception $e) {
			throw new LocalizedException(__( "Documento fiscal inválido, CPF ou CNPJ não preenchido."));
			return $this;
		}

		
		return $customer;
	}

	public function getTypeForCNPJ(){
		$typecpf = $this->_scopeConfig->getValue('payment/moipbase/advanced/type_cnpj', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		
		return $typecpf;
	}

	public function getTypeForCpf(){
		$typecpf = $this->_scopeConfig->getValue('payment/moipbase/advanced/type_cpf', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		
		return $typecpf;
	}

	public function getTypeNameCompany(){
		$type_name_company = $this->_scopeConfig->getValue('payment/moipbase/advanced/type_name_company', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

		return $type_name_company;
	}

	public function getCpfAttributeForCustomer(){
		$attribute_cpf = $this->_scopeConfig->getValue('payment/moipbase/advanced/cpf_for_customer', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		
		return $attribute_cpf;
	}

	public function getCpfAttributeForAddress(){
		$attribute_cpf = $this->_scopeConfig->getValue('payment/moipbase/advanced/cpf_for_address', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		
		return $attribute_cpf;
	}

	public function getCNPJAttributeForCustomer(){
		$attribute_cpf = $this->_scopeConfig->getValue('payment/moipbase/advanced/cnpj_for_customer', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		
		return $attribute_cpf;
	}

	public function getCNPJAttributeForAddress(){
		$attribute_cpf = $this->_scopeConfig->getValue('payment/moipbase/advanced/cnpj_for_address', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		
		return $attribute_cpf;
	}

	public function getCompanyAttributeForAddress(){
		$attribute_cpf = $this->_scopeConfig->getValue('payment/moipbase/advanced/company_name_address', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		
		return $attribute_cpf;
	}

	public function getCompanyAttributeForCustomer(){
		$attribute_cpf = $this->_scopeConfig->getValue('payment/moipbase/advanced/company_name_customer', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		
		return $attribute_cpf;
	}

	public function getStreetPositionLogradouro(){
		$street_logradouro = $this->_scopeConfig->getValue('payment/moipbase/advanced/street_logradouro', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		
		return $street_logradouro;
	}

	public function getStreetPositionNumber(){
		$street_logradouro = $this->_scopeConfig->getValue('payment/moipbase/advanced/street_number', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		
		return $street_logradouro;
	}

	public function getStreetPositionComplemento(){
		$street_logradouro = $this->_scopeConfig->getValue('payment/moipbase/advanced/street_complemento', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		
		return $street_logradouro;
	}

	public function getStreetPositionDistrict(){
		$street_logradouro = $this->_scopeConfig->getValue('payment/moipbase/advanced/street_district', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		
		return $street_logradouro;
	}

    public function initOrderMoip($moip, $order){
    	
    	$moipOrder = $moip->orders()->setOwnId($order->getIncrementId());
    	return $moipOrder;
    }


    public function addProductItemsMoip($moipOrder, $items){

    	foreach ($items as $item) {
			if ($item->getParentItem()) continue;
			if ($item->getPrice() == 0) continue;
				$name = $item->getName();
				$sku = $item->getSku();
				$qty = $item->getQty();
				$price = $item->getPrice();
				$price = ($price * self::ROUND_UP);
				$setprice = (int)$price;
				$setqty = (int)$qty;
				$moipOrder->addItem("$name",$setqty, "$sku", $setprice);
		}
		return $moipOrder;
    }

    public function addShippingPriceMoip($moipOrder, $order){

    	$shipping = $order->getShippingAmount() * self::ROUND_UP;
		$shipping = (int)$shipping;
		$moipOrder->setShippingAmount($shipping);
		return $moipOrder;
    }

    public function addDiscountPriceMoip($moipOrder, $order){

    	$discount = $order->getDiscountAmount() * self::ROUND_UP;
		$discount = (int)$discount;
		$discount = -($discount);
		$moipOrder->setDiscount($discount);
		return $moipOrder;
    }

    public function addAdditionalPriceMoip($moipOrder, $order, $count = null){

		$tax = $order->getTaxAmount()* self::ROUND_UP;
		$tax = (int)$tax;
		
		if($count > 1){
			$rate 				= $this->getRate($count);
			$type_interest		= $this->getTypeInterest();
			if($type_interest == "compound"){
				$parcela 			= $this->getJurosComposto($order->getGrandTotal(), $rate, $count);
			} else {
				$parcela 			= $this->getJurosSimples($order->getGrandTotal(), $rate, $count);
			}
			 
			$total_parcelado 	= $parcela * $count;
			$additionalPrice 	= $total_parcelado - $order->getGrandTotal();
			$additionalPrice 	= number_format((float)$additionalPrice, 2, '.', '') * self::ROUND_UP; 
			$additionalPrice 	= $additionalPrice + $tax;
			
		} else {
			$additionalPrice =  $tax;
		}

		$moipOrder->setAddition($additionalPrice);

		return $moipOrder;
    }

    public function addPayBoletoMoip($moipOrder){

    	
    	$number_date = $this->getDueNumber();
		$expiration_date = $this->getDateDue($number_date);
		$mediaUrl = $this ->_storeManager-> getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA );

		$logo_uri = "";

		$instruction_lines = [$this->getInstructionLines(1), $this->getInstructionLines(2), $this->getInstructionLines(3)];
				
		$payMoip = $moipOrder->payments() 
						        ->setBoleto($expiration_date, $logo_uri, $instruction_lines)
						        ->execute();
		return $payMoip;
    }

    public function addPayCcMoip($moipOrder, $order, $InfoInstance, $payment){
    	$moip = $this->AuthorizationValidate();


		if (!$order->getCustomerFirstname()) {
				$name = $order->getBillingAddress()->getName();
		} else {
				$name = $order->getCustomerFirstname() . ' ' . $order->getCustomerLastname();
		}

		$type_cpf 	= $this->getTypeForCpf();

		if($type_cpf === "customer"){
			$attribute_cpf_customer = $this->getCpfAttributeForCustomer();
			$_taxvat = $order->getData('customer_'.$attribute_cpf_customer);
		} else {
			$attribute_cpf_address = $this->getCpfAttributeForAddress();
			$_taxvat = $order->getBillingAddress()->getData($attribute_cpf_address);
		}
		if($InfoInstance->getAdditionalInformation('document')){
			$_taxvat = $InfoInstance->getAdditionalInformation('document');
		}
		

		$taxvat = preg_replace("/[^0-9]/", "",$_taxvat);

		$type_cnpj 	= $this->getTypeForCNPJ();

		if($type_cnpj === "use_cpf"){

			if(strlen($taxvat) === 14) {
				$_typedocument = "CNPJ";
				$type_name_company = $this->getTypeNameCompany();

				if($type_name_company === "customer"){
					$attribute_name = $this->getCompanyAttributeForCustomer();
					$name 		= $order->getData('customer_'.$attribute_name);
				} else {
					$attribute_name = $this->getCompanyAttributeForAddress();
					$name 		= $order->getBillingAddress()->getData($attribute_name);
				}

			} else {
				$_typedocument = "CPF";
			}

		} elseif ($type_cnpj === "use_customer") {
			$attribute_cnpj = $this->getCNPJAttributeForCustomer();
			$_taxvat 		= $order->getData('customer_'.$attribute_cnpj);
			if($_taxvat){
				$_typedocument = "CNPJ";
				$type_name_company = $this->getTypeNameCompany();
				if($type_name_company === "customer"){
					$attribute_name = $this->getCompanyAttributeForCustomer();
					$name 		= $order->getData('customer_'.$attribute_name);
				} else {
					$attribute_name = $this->getCompanyAttributeForAddress();
					$name 		= $order->getBillingAddress()->getData($attribute_name);
				}

			}
		} elseif($type_cnpj === "use_address"){
			$attribute_cnpj_address = $this->getCNPJAttributeForAddress();
			$_taxvat = $order->getBillingAddress()->getData($attribute_cnpj_address);
			if($_taxvat){
				$_typedocument = "CNPJ";
				$type_name_company = $this->getTypeNameCompany();
				if($type_name_company === "customer"){
					$attribute_name = $this->getCompanyAttributeForCustomer();
					$name 		= $order->getData('customer_'.$attribute_name);
				} else {
					$attribute_name = $this->getCompanyAttributeForAddress();
					$name 		= $order->getBillingAddress()->getData($attribute_name);
				}
			}
		}

		$taxvat = preg_replace("/[^0-9]/", "",$_taxvat);
		
		$email = $order->getCustomerEmail();
		
		$dob = $order->getCustomerDob()
            		? date('Y-m-d', strtotime($order->getCustomerDob()))
            		: '1985-10-10';
		
		$ddd_telephone 		= $this->getNumberOrDDD($order->getBillingAddress()->getTelephone(), true);
		$number_telephone 	= $this->getNumberOrDDD($order->getBillingAddress()->getTelephone(), false);

		$street_billing  	= $order->getBillingAddress()->getStreet();
		
		$city_billing 		= $order->getBillingAddress()->getData('city');
		
		$region_billing 	= $order->getBillingAddress()->getRegionCode();
		
		$postcode_billing 	= substr(preg_replace("/[^0-9]/", "", $order->getBillingAddress()->getData('postcode')) . '00000000', 0, 8);
		
		$billing_logradouro 	= $street_billing[$this->getStreetPositionLogradouro()];

		$billing_number 		= $street_billing[$this->getStreetPositionNumber()];
		
		if(count($street_billing) >= 3){
			$billing_district 		= $street_billing[$this->getStreetPositionDistrict()];
		} else {
			$billing_district 		= $street_billing[$this->getStreetPositionLogradouro()];
		}


		if(count($street_billing) == 4){
			$billing_complemento	= $street_billing[$this->getStreetPositionComplemento()];
		} else {
			$billing_complemento	= "";
		}


		if (!$order->getIsVirtual()) {
			$city_shipping 		= $order->getShippingAddress()->getData('city');
			$street_shipping 	= $order->getShippingAddress()->getStreet();
			$region_shipping 	= $order->getShippingAddress()->getRegionCode();
			$postcode_shipping 	= substr(preg_replace("/[^0-9]/", "", $order->getShippingAddress()->getData('postcode')) . '00000000', 0, 8);

			$shipping_logradouro 	= $street_shipping[$this->getStreetPositionLogradouro()];

			$shipping_number 		= $street_shipping[$this->getStreetPositionNumber()];

			if(count($street_billing) >= 3) {
				$shipping_district 		= $street_shipping[$this->getStreetPositionDistrict()];
			} else {
				$shipping_district 		= $street_shipping[$this->getStreetPositionLogradouro()];
			}
			

			if(count($street_shipping) == 4){
				$shipping_complemento	=  $street_shipping[$this->getStreetPositionComplemento()];
			} else {
				$shipping_complemento	=  "";
			}
		}



		$holder =  $moip->holders()
			        ->setFullname($InfoInstance->getAdditionalInformation('fullname'))
			       
			        ->setBirthDate($dob)
			        ->setTaxDocument($taxvat, $_typedocument)
			        ->setPhone($ddd_telephone, $number_telephone)
			        	->setAddress('BILLING',
								            $billing_logradouro, 
								            $billing_number,
								            $billing_district, 
								            $city_billing, 
								            $region_billing,
								            $postcode_billing, 
								            $billing_complemento
								     );
    	
					
		$payMoip = $moipOrder->payments()->setCreditCardHash(
					$InfoInstance->getAdditionalInformation('hash'),
					$holder
			)
		->setInstallmentCount($InfoInstance->getAdditionalInformation('installments'))
		->execute();
		return $payMoip;
    }


    public function getDateDue($NDias)
    {
        $date = $this->date->gmtDate('Y-m-d', strtotime("+{$NDias} days"));
      
        return  $date;
    }

    public function getJurosSimples($valor, $juros, $parcela)
    {
        $principal = $valor;
        $taxa = $juros/100;
        $valjuros = $principal * $taxa;
        $valParcela = ($principal + $valjuros)/$parcela;
        return $valParcela;
    }
    
    public function getJurosComposto($valor, $juros, $parcela)
    { 
        $principal = $valor;
        $taxa = $juros/100;
        $valParcela = ($principal * $taxa) / (1 - (pow(1 / (1 + $taxa), $parcela)));
		return $valParcela;
    }

    public function getNumberOrDDD($param_telefone, $param_ddd = false)
    {
        $cust_ddd       = '11';
        $cust_telephone = preg_replace("/[^0-9]/", "", $param_telefone);
        if(strlen($cust_telephone) == 11){
            $st             = strlen($cust_telephone) - 9;
            $indice         = 9;
        } else {
            $st             = strlen($cust_telephone) - 8;
            $indice         = 8;
        }
        
        if ($st > 0) {
            $cust_ddd       = substr($cust_telephone, 0, 2);
            $cust_telephone = substr($cust_telephone, $st, $indice);
        }
        if ($param_ddd === false) {
            $retorno = $cust_telephone;
        } else {
            $retorno = $cust_ddd;
        }
        return $retorno;
    }

    public function getInstructionLines($line) 
	{
		$instrucao1 = $this->_scopeConfig->getValue('payment/moipboleto/instrucao'.$line, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		
		return $instrucao1;
	}

	public function getTypeInterest(){
		$type_interest = $this->_scopeConfig->getValue('payment/moipcc/installment/type_interest', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		
		return $type_interest;

	}

	public function getRate($installment) 
	{
		$rate = $this->_scopeConfig->getValue('payment/moipcc/installment/installment_'.$installment, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		
		return $rate;
	}

	public function getDueNumber() 
	{
		$instrucao1 = $this->_scopeConfig->getValue('payment/moipboleto/expiration', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		
		return $instrucao1;
	}

	public function getImgForBoleto()
	{
		/*logo_boleto*/
		$logo_boleto = $this->_scopeConfig->getValue('payment/moipboleto/logo_boleto', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		
		return $logo_boleto;
	}


	public function getOauth($environment) 
    {
        $oauth = $this->_scopeConfig->getValue('payment/moipbase/oauth_'.$environment, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        
        return $oauth;
    }


    public function getEnvironmentMode() 
    {
        $environment = $this->_scopeConfig->getValue('payment/moipbase/environment_mode', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        
        return $environment;
    }

    public function getInfoUrlPreferenceInfo($type) 
    {
        $_environment 	= $this->getEnvironmentMode();
        $id          	= $this->_scopeConfig->getValue(
                                                            'payment/moipbase/'.$type.'_id_'.$_environment, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        
        return $id;
    }

    public function getInfoUrlPreferenceToken($type) 
    {
        $_environment 	= $this->getEnvironmentMode();
        $token          = $this->_scopeConfig->getValue(
                                                            'payment/moipbase/'.$type.'_token_'.$_environment, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        
        return $token;
    }

    public function getStateOrderMoip($moip_order_id){
		$moip = $this->AuthorizationValidate();
		$order = $moip->orders()->get($moip_order_id);
		return $order->getStatus();
	}
}
