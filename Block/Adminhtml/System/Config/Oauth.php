<?php
/**
 * Copyright © Moip by PagSeguro. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */

namespace Moip\Magento2\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Moip\Magento2\Gateway\Config\Config;

/**
 * Class Oauth - Defines oAuth session actions.
 */
class Oauth extends Field
{
    /**
     * @var string
     */
    protected $_template = 'Moip_Magento2::system/config/oauth.phtml';

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param Config  $config
     * @param Context $context
     */
    public function __construct(
        Config $config,
        Context $context
    ) {
        $this->config = $config;
        parent::__construct($context);
    }

    /**
     * Render.
     *
     * @param AbstractElement $element
     *
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();

        return parent::render($element);
    }

    /**
     * Elment Html.
     *
     * @param AbstractElement $element
     *
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    /**
     * Ajax Url.
     *
     * @return string
     */
    public function getAjaxUrl()
    {
        return $this->getUrl('moip/system_config/logout');
    }

    /**
     * Url Authorize.
     *
     * @return string
     */
    public function getUrlAuthorize()
    {
        $baseUri = Config::OAUTH_URI;
        $storeUri = $this->getUrl('moip/system_config/oauth');

        return $baseUri.'?client_id='.$storeUri;
    }

    /**
     * Button Html.
     *
     * @return string
     */
    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock(
            \Magento\Backend\Block\Widget\Button::class
        )->setData(
            [
                'id'    => 'oauth',
                'label' => __($this->getInfoTextBtn()),
            ]
        );

        return $button->toHtml();
    }

    /**
     * Info Text Button.
     *
     * @return string
     */
    public function getInfoTextBtn()
    {
        $environment = $this->config->getEnvironmentMode();
        $oauth = $this->config->getMerchantGatewayOauth();
        $label = __('Production');

        if ($environment === Config::ENVIRONMENT_SANDBOX) {
            $label = __('Environment for tests');
        }

        $text = sprintf(__('Authorize in %s'), $label);

        if ($oauth) {
            $text = sprintf(__('Disallow in %s'), $label);
        }

        return $text;
    }

    /**
     * Type Js.
     *
     * @return string
     */
    public function getTypeJs()
    {
        if ($this->config->getMerchantGatewayOauth()) {
            return 'clear';
        }

        return 'getautorization';
    }

    /**
     * Url to connect.
     *
     * @return string
     */
    public function getUrlToConnect()
    {
        $redirectUri = $this->getUrlAuthorize();
        $endpointOauth = Config::ENDPOINT_OAUTH_PRODUCTION;
        $appId = Config::APP_ID_PRODUCTION;
        $scope = Config::OAUTH_SCOPE;
        $responseType = 'code';

        if ($this->config->getEnvironmentMode() === Config::ENVIRONMENT_SANDBOX) {
            $endpointOauth = Config::ENDPOINT_OAUTH_SANDBOX;
            $appId = Config::APP_ID_SANDBOX;
        }

        $link = $endpointOauth;
        $link .= '?response_type='.$responseType;
        $link .= '&client_id='.$appId;
        $link .= '&redirect_uri='.$redirectUri;
        $link .= '&scope='.$scope;

        return $link;
    }
}
