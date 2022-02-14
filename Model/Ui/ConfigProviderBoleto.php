<?php
/**
 * Copyright © Moip by PagSeguro. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */

namespace Moip\Magento2\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Escaper;
use Magento\Framework\View\Asset\Source;
use Magento\Payment\Model\CcConfig;
use Magento\Quote\Api\Data\CartInterface;
use Moip\Magento2\Gateway\Config\ConfigBoleto;

/**
 * Class ConfigProviderBoleto - Defines properties of the payment form..
 */
class ConfigProviderBoleto implements ConfigProviderInterface
{
    /*
     * @const string
     */
    public const CODE = 'moip_magento2_boleto';

    /**
     * @var Config
     */
    private $config;

    /**
     * @var CartInterface
     */
    private $cart;

    /**
     * @var array
     */
    private $icons = [];

    /**
     * @var CcConfig
     */
    protected $ccConfig;

    /**
     * @var Source
     */
    protected $assetSource;

    /**
     * @param ConfigBoleto  $config
     * @param CartInterface $cart
     * @param CcConfig      $ccConfig
     * @param Escaper       $escaper
     * @param Source        $assetSource
     */
    public function __construct(
        ConfigBoleto $config,
        CartInterface $cart,
        CcConfig $ccConfig,
        Escaper $escaper,
        Source $assetSource
    ) {
        $this->config = $config;
        $this->cart = $cart;
        $this->escaper = $escaper;
        $this->ccConfig = $ccConfig;
        $this->assetSource = $assetSource;
    }

    /**
     * Retrieve assoc array of checkout configuration.
     *
     * @return array
     */
    public function getConfig()
    {
        $storeId = $this->cart->getStoreId();

        return [
            'payment' => [
                ConfigBoleto::METHOD => [
                    'isActive'             => $this->config->isActive($storeId),
                    'title'                => $this->config->getTitle($storeId),
                    'name_capture'         => $this->config->getUseNameCapture($storeId),
                    'tax_document_capture' => $this->config->getUseTaxDocumentCapture($storeId),
                    'expiration'           => nl2br(
                        $this->escaper->escapeHtml(
                            $this->config->getExpirationFormat($storeId)
                        )
                    ),
                    'instruction_checkout' => nl2br(
                        $this->escaper->escapeHtml(
                            $this->config->getInstructionCheckout($storeId)
                        )
                    ),
                    'logo'                 => $this->getLogo(),
                ],
            ],
        ];
    }

    /**
     * Get icons for available payment methods.
     *
     * @return array
     */
    public function getLogo()
    {
        $logo = [];
        $asset = $this->ccConfig->createAsset('Moip_Magento2::images/boleto/moipboleto.svg');
        $placeholder = $this->assetSource->findSource($asset);
        if ($placeholder) {
            list($width, $height) = getimagesizefromstring($asset->getSourceFile());
            $logo = [
                'url'    => $asset->getUrl(),
                'width'  => $width,
                'height' => $height,
                'title'  => __('Moip'),
            ];
        }

        return $logo;
    }
}
