<?php
/**
 * Copyright © Moip by PagSeguro. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */

namespace Moip\Magento2\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\View\Asset\Source;
use Magento\Payment\Model\CcConfig;
use Magento\Quote\Api\Data\CartInterface;
use Moip\Magento2\Gateway\Config\Config as ConfigBase;
use Moip\Magento2\Gateway\Config\ConfigCc;

/**
 * Class ConfigProviderCc - Defines properties of the payment form..
 */
class ConfigProviderCc implements ConfigProviderInterface
{
    /*
     * @const string
     */
    public const CODE = 'moip_magento2_cc';

    /*
     * @var VAULT CODE
     */
    public const VAULT_CODE = 'moip_magento2_cc_vault';

    /**
     * @var Config Base
     */
    private $configBase;

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
     * @param ConfigBase    $configBase
     * @param ConfigCc      $config
     * @param CartInterface $cart
     * @param CcConfig      $ccConfig
     * @param Source        $assetSource
     */
    public function __construct(
        ConfigBase $configBase,
        ConfigCc $config,
        CartInterface $cart,
        CcConfig $ccConfig,
        Source $assetSource
    ) {
        $this->configBase = $configBase;
        $this->config = $config;
        $this->cart = $cart;
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
                ConfigCc::METHOD => [
                    'public_key'           => $this->configBase->getMerchantGatewayKeyPublic($storeId),
                    'isActive'             => $this->config->isActive($storeId),
                    'title'                => $this->config->getTitle($storeId),
                    'useCvv'               => $this->config->isCvvEnabled($storeId),
                    'ccTypesMapper'        => $this->config->getCcTypesMapper($storeId),
                    'logo'                 => $this->getLogo(),
                    'icons'                => $this->getIcons(),
                    'tax_document_capture' => $this->config->getUseTaxDocumentCapture($storeId),
                    'birth_date_capture'   => $this->config->getUseBirthDateCapture($storeId),
                    'phone_capture'        => $this->config->getUsePhoneCapture($storeId),
                    'type_interest'        => $this->config->getTypeInstallment($storeId),
                    'info_interest'        => $this->config->getInfoInterest($storeId),
                    'min_installment'      => $this->config->getMinInstallment($storeId),
                    'max_installment'      => $this->config->getMaxInstallment($storeId),
                    'ccVaultCode'          => self::VAULT_CODE,
                ],
            ],
        ];
    }

    /**
     * Get icons for available payment methods.
     *
     * @return array
     */
    public function getIcons()
    {
        if (!empty($this->icons)) {
            return $this->icons;
        }
        $storeId = $this->cart->getStoreId();
        $ccTypes = $this->config->getCcAvailableTypes($storeId);
        $types = explode(',', $ccTypes);
        foreach ($types as $code => $label) {
            if (!array_key_exists($code, $this->icons)) {
                $asset = $this->ccConfig->createAsset('Moip_Magento2::images/cc/'.strtolower($label).'.svg');
                $placeholder = $this->assetSource->findSource($asset);
                if ($placeholder) {
                    list($width, $height) = getimagesizefromstring($asset->getSourceFile());
                    $this->icons[$label] = [
                        'url'    => $asset->getUrl(),
                        'width'  => $width,
                        'height' => $height,
                        'title'  => __($label),
                    ];
                }
            }
        }

        return $this->icons;
    }

    /**
     * Get Cvv.
     *
     * @return array
     */
    public function getImageCvv()
    {
        $imageCvv = null;
        $asset = $this->ccConfig->createAsset('Moip_Magento2::images/cc/cvv.gif');
        $placeholder = $this->assetSource->findSource($asset);
        if ($placeholder) {
            $imageCvv = $asset->getUrl();
        }

        return $imageCvv;
    }

    /**
     * Get icons for available payment methods.
     *
     * @return array
     */
    public function getLogo()
    {
        $logo = [];
        $asset = $this->ccConfig->createAsset('Moip_Magento2::images/cc/credit-card.svg');
        $placeholder = $this->assetSource->findSource($asset);
        if ($placeholder) {
            list($width, $height) = getimagesizefromstring($asset->getSourceFile());
            $logo = [
                'url'    => $asset->getUrl(),
                'width'  => $width,
                'height' => $height,
                'title'  => __('Moip by PagSeguro'),
            ];
        }

        return $logo;
    }
}
