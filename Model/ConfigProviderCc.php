<?php
namespace Moip\Magento2\Model;

use Magento\Framework\Locale\Bundle\DataBundle;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Escaper;
use Magento\Payment\Model\CcConfig;
use Magento\Framework\View\Asset\Source;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Customer\Model\Session;


class ConfigProviderCc implements ConfigProviderInterface
{
	/**
     * Years range
     */
    const YEARS_RANGE = 20;
    /**
     * @var string[]
     */
    protected $methodCodes = [
        'moipcc'
    ];

	protected $_ccoptions = [
        'mastercard' => 'Mastercard',
        'visa' => 'Visa',
        'amex' => 'American Express',
		'diners' => 'Diners',
        'elo' => 'Elo',
        'hipercard' => 'Hipercard',
		'hiper' => 'HIPER'
    ];
    /**
     * @var \Magento\Payment\Model\Method\AbstractMethod[]
     */
    protected $methods = [];

    /**
     * @var Escaper
     */
    protected $escaper;

    /**
     * @param PaymentHelper $paymentHelper
     * @param Escaper $escaper
     */
    /**
     * @var array
     */
    private $icons = [];

    /**
     * @var CcConfig
     */
    protected $ccConfig;
    /**
     * @var \Magento\Framework\Locale\ResolverInterface
     */
    protected $localeResolver;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_helper;
    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    protected $_priceCurrency;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var \Magento\Framework\View\Asset\Source
     */
    protected $assetSource;
    protected $_priceFiler;

    /**
     * ConfigProvider constructor.
     * @param PaymentHelper $paymentHelper
     * @param Escaper $escaper
     * @param CcConfig $ccConfig
     * @param Source $assetSource
     * @param \Magento\Framework\Locale\ResolverInterface $localeResolver
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param Session $customerSession
     * @param \Magento\Checkout\Model\Session $_checkoutSession
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        PaymentHelper $paymentHelper,
        Escaper $escaper,
        CcConfig $ccConfig,
        Source $assetSource,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        Session $customerSession,
        \Magento\Checkout\Model\Session $_checkoutSession,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Magento\Framework\Pricing\Helper\Data $priceFilter
    ) {
        $this->ccConfig = $ccConfig;
        $this->assetSource = $assetSource;
        $this->escaper = $escaper;
        $this->localeResolver = $localeResolver;
        $this->_date = $date;
        $this->_priceCurrency = $priceCurrency;
        $this->_customerSession = $customerSession;
        $this->_checkoutSession = $_checkoutSession;
        $this->scopeConfig = $scopeConfig;
        $this->_priceFiler = $priceFilter;
        foreach ($this->methodCodes as $code) {
            $this->methods[$code] = $paymentHelper->getMethodInstance($code);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
		$config = [];
        foreach ($this->methodCodes as $code) {
            if ($this->methods[$code]->isAvailable()) {
                $config['payment'][$code]['ccavailabletypes'] = $this->getCcAvailableTypes();
                $config['payment'][$code]['years'] = $this->getYears();
                $config['payment'][$code]['months'] = $this->getMonths();
                $config['payment'][$code]['icons'] = $this->getIcons();
				$config['payment'][$code]['currency'] = $this->getCurrencyData();
                $config['payment'][$code]['type_interest'] = $this->TypeInstallment();
				$config['payment'][$code]['info_interest'] = $this->getInfoParcelamentoJuros();
				$config['payment'][$code]['max_installment'] = $this->MaxInstallment();
				$config['payment'][$code]['min_installment'] = $this->MinInstallment();
                $config['payment'][$code]['publickey'] = $this->getPublicKey();
                $config['payment'][$code]['image_cvv'] = $this->getCvvImg();
                $config['payment'][$code]['get_document'] = $this->getUseDocument();
			}
        }
		
        return $config;
    }
	
    /**
     * @return array
     */
    protected function getCcAvailableTypes()
    {
        return $this->_ccoptions;
    }

    public function getCvvImg(){
        $asset = $this->ccConfig
                    ->createAsset('Moip_Magento2::images/cc/cvv.gif');
        return $asset->getUrl();
    }
    /**
     * @return array
     */
    public function getIcons()
    {
        if (!empty($this->icons)) {
            return $this->icons;
        }

        $types = $this->_ccoptions;
        foreach (array_keys($types) as $code) {

            if (!array_key_exists($code, $this->icons)) {
                $asset = $this->ccConfig
                    ->createAsset('Moip_Magento2::images/cc/' . strtolower($code) . '.png');
                $placeholder = $this->assetSource->findSource($asset);
                if ($placeholder) {
                    list($width, $height) = getimagesize($asset->getSourceFile());
                    $this->icons[$code] = [
                        'url' => $asset->getUrl(),
                        'width' => $width,
                        'height' => $height
                    ];
                }

            }
        }
        return $this->icons;
    }

    /**
     * @return array
     */
    public function getMonths()
    {
        $data = [];
        $months = (new DataBundle())->get(
            $this->localeResolver->getLocale()
        )['calendar']['gregorian']['monthNames']['format']['wide'];
        foreach ($months as $key => $value) {
            $monthNum = ++$key < 10 ? '0' . $key : $key;
            $data[$key] = $monthNum . ' - ' . $value;
        }
        return $data;
    }

    /**
     * @return array
     */
    public function getYears()
    {
        $years = [];
        $first = (int)$this->_date->date('Y');
        for ($index = 0; $index <= self::YEARS_RANGE; $index++) {
            $year = $first + $index;
            $years[$year] = $year;
        }
        return $years;
    }

    public function getUseDocument(){
      return $this->scopeConfig->getValue("payment/moipcc/document/getdocument");
    }
	
	public function getInfoParcelamentoJuros() {
        $juros = [];
        $juros['0'] = 0;
		$juros['1'] = 0;

        $juros['2'] =  $this->scopeConfig->getValue("payment/moipcc/installment/installment_2");

        
        $juros['3'] =  $this->scopeConfig->getValue("payment/moipcc/installment/installment_3");

        
        $juros['4'] =  $this->scopeConfig->getValue("payment/moipcc/installment/installment_4");

        
        $juros['5'] =  $this->scopeConfig->getValue("payment/moipcc/installment/installment_5");


        $juros['6'] =  $this->scopeConfig->getValue("payment/moipcc/installment/installment_6");


        $juros['7'] =  $this->scopeConfig->getValue("payment/moipcc/installment/installment_7");


        $juros['8'] =  $this->scopeConfig->getValue("payment/moipcc/installment/installment_8");


        $juros['9'] =  $this->scopeConfig->getValue("payment/moipcc/installment/installment_9");
       

        $juros['10'] = $this->scopeConfig->getValue("payment/moipcc/installment/installment_10");
       

        $juros['11'] = $this->scopeConfig->getValue("payment/moipcc/installment/installment_11");
       

        $juros['12'] = $this->scopeConfig->getValue("payment/moipcc/installment/installment_12");
       
        return $juros;
    }

	public function getCurrencyData()
    {
        $currencySymbol = $this->_priceCurrency
            ->getCurrency()->getCurrencySymbol();
        return $currencySymbol;
    }
	
    public function TypeInstallment()
    {
        $type = $this->scopeConfig->getValue('payment/moipcc/installment/type_interest');
        return $type;
    }

	public function MinInstallment()
    {
        $parcelasMinimo = $this->scopeConfig->getValue('payment/moipcc/installment/min_installment');
        return $parcelasMinimo;
    }
	
	public function MaxInstallment()
    {
        $parcelasMaximo = $this->scopeConfig->getValue('payment/moipcc/installment/max_installment');
        return $parcelasMaximo;
    }

    public function getEnvironmentMode() 
    {
        $environment = $this->scopeConfig->getValue('payment/moipbase/environment_mode');
        
        return $environment;
    }

    public function getPublicKey()
    {
        $_environment = $this->getEnvironmentMode();
        $publickey = $this->scopeConfig->getValue('payment/moipbase/publickey_'.$_environment);
        return $publickey;
    }
}
