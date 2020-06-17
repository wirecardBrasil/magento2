<?php
namespace Moip\Magento2\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Escaper;
use Magento\Payment\Model\CcConfig;
use Magento\Framework\View\Asset\Source;
use Magento\Payment\Helper\Data as PaymentHelper;


class ConfigProviderBoleto implements ConfigProviderInterface
{
	
   /**
     * @var string[]
     */
    protected $methodCode = "moipboleto";

    /**
     * @var Checkmo
     */
    protected $method;

    /**
     * @var Escaper
     */
    protected $escaper;

    protected $scopeConfig;
    /**
     * @var CcConfig
     */
    protected $ccConfig;

    /**
     * @var array
     */
    private $icon = [];

    protected $assetSource;
    /**
     * @param PaymentHelper $paymentHelper
     * @param Escaper $escaper
     */
    public function __construct(
        PaymentHelper $paymentHelper,
        Escaper $escaper,
        CcConfig $ccConfig,
        Source $assetSource,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->ccConfig = $ccConfig;
        $this->escaper = $escaper;
        $this->assetSource = $assetSource;
        $this->method = $paymentHelper->getMethodInstance($this->methodCode);
        $this->scopeConfig = $scopeConfig;

    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        return $this->method->isAvailable() ? [
            'payment' => [
                'moipboleto' => [
                    'instruction' =>  $this->getInstruction(),
                    'due' => $this->getDue(),
                    'icon' => $this->getIcon()
                ],
            ],
        ] : [];
    }

    /**
     * @return array
     */
    public function getIcon()
    {
        $asset = $this->ccConfig
                    ->createAsset('Moip_Magento2::images/boleto/moipboleto.svg');
        return $asset->getUrl();
    }

    /**
     * Get instruction from config
     *
     * @return string
     */
    protected function getInstruction()
    {
        return nl2br($this->escaper->escapeHtml($this->scopeConfig->getValue("payment/moipboleto/instruction")));
    }


    /**
     * Get due from config
     *
     * @return string
     */
    protected function getDue()
    {
        $day = (int)$this->scopeConfig->getValue("payment/moipboleto/expiration");
        if($day > 1) {
            return nl2br(__('Expiration in %1 days', $day));    
        } else {
            return nl2br(__('Expiration in %1 day', $day));;    
        }
        
    }


}
