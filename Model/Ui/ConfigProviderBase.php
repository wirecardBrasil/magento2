<?php
/**
 * Copyright © Wirecard Brasil. All rights reserved.
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
     * @var CODE
     */
    const CODE = 'moip_magento2';

    /*
     * @var METHOD CODE CC
     */
    const METHOD_CODE_CC = 'moip_magento2_cc';

    /*
     * @var METHOD CODE BOLETO
     */
    const METHOD_CODE_BOLETO = 'moip_magento2_boleto';

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
     * @var \Magento\Framework\View\Asset\Source
     */
    protected $assetSource;

    /**
     * @param Config        $config
     * @param CartInterface $cart
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
