<?php
/**
 * Copyright © Moip by PagSeguro. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */

namespace Moip\Magento2\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Payment\Model\CcConfig;
use Magento\Quote\Api\Data\CartInterface;
use Moip\Magento2\Gateway\Config\Config;

/**
 * Class ConfigProviderBase - Defines properties of the payment form.
 */
class ConfigProviderBase implements ConfigProviderInterface
{
    /*
     * @const string
     */
    public const CODE = 'moip_magento2';

    /*
     * @var METHOD CODE CC
     */
    public const METHOD_CODE_CC = 'moip_magento2_cc';

    /*
     * @var METHOD CODE CC VAULT
     */
    public const METHOD_CODE_CC_VAULT = 'moip_magento2_cc_vault';

    /*
     * @var METHOD CODE BOLETO
     */
    public const METHOD_CODE_BOLETO = 'moip_magento2_boleto';

    /*
     * @var METHOD CODE CHECKOUT
     */
    public const METHOD_CODE_CHECKOUT = 'moip_magento2_checkout';

    /**
     * @var Config
     */
    private $config;

    /**
     * @var CartInterface
     */
    private $cart;

    /**
     * @var CcConfig
     */
    protected $ccConfig;

    /**
     * @param Config        $config
     * @param CartInterface $cart
     * @param CcConfig      $ccConfig
     */
    public function __construct(
        Config $config,
        CartInterface $cart,
        CcConfig $ccConfig
    ) {
        $this->config = $config;
        $this->cart = $cart;
        $this->ccConfig = $ccConfig;
    }

    /**
     * Retrieve assoc array of checkout configuration.
     *
     * @return array
     */
    public function getConfig()
    {
        return [
            'payment' => [
                Config::METHOD => [
                    'isActive' => false,
                ],
            ],
        ];
    }
}
