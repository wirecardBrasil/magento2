<?php

namespace Moip\Magento2\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Moip\Auth\Connect;
class Oauth extends Field
{
   
    protected $_template = 'Moip_Magento2::system/config/oauth.phtml';

    public function __construct(
        \Moip\Magento2\Helper\Data $moipHelper,
        Context $context
    ) {
        $this->_moipHelper = $moipHelper;
        parent::__construct($context);
    }

    
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

   
    public function getAjaxUrl()
    {
        return $this->getUrl('moip/system_config/logout');
    }

    public function getUrlAuthorize()
    {
        return $this->getUrl('moip/system_config/oauth');
    }

   
    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock(
            'Magento\Backend\Block\Widget\Button'
        )->setData(
            [
                'id' => 'oauth',
                'label' => __($this->getInfoTextBtn()),
            ]
        );

        return $button->toHtml();
    }

    public function getInfoTextBtn(){
        
        $_environment = $this->_moipHelper->getEnvironmentMode();
        if($_environment === "production"){
            $label = __('Production');
        } else {
            $label = __('Environment for tests');
        }

        if($this->_moipHelper->getOauth($_environment)){
            $text = sprintf(__('Disallow in %s'), $label);
        } else {
            $text = sprintf(__('Authorize in %s'), $label);
        }
        return $text;
    }

    public function getTypeJs(){
        $_environment = $this->_moipHelper->getEnvironmentMode();
        if($this->_moipHelper->getOauth($_environment)){
            return "clear";
        } else {
            return "getautorization";
        }
    }
   

    public function getUrltoConnect(){

        $_url_cliente_id = $this->getUrlAuthorize();
        $_environment = $this->_moipHelper->getEnvironmentMode();
        if($_environment == "production") {
            $redirect_uri   = $this->_moipHelper::REDIRECT_URI_PRODUCTION;
            $client_id      = $this->_moipHelper::APP_ID_PRODUCTION;
        } else {
            $redirect_uri   = $this->_moipHelper::REDIRECT_URI_SANDBOX;
            $client_id      = $this->_moipHelper::APP_ID_SANDBOX;
        }
        $redirect_uri = $redirect_uri.'?cliente_id='.$_url_cliente_id;
       
        $scope = true;
        
        if($_environment == "production") {
            $connect = new Connect($redirect_uri, $client_id, $scope, Connect::ENDPOINT_PRODUCTION);
        } else {
           $connect = new Connect($redirect_uri, $client_id, $scope, Connect::ENDPOINT_SANDBOX);
        }

        $connect->setScope(Connect::RECEIVE_FUNDS)
            ->setScope(Connect::REFUND)
            ->setScope(Connect::MANAGE_ACCOUNT_INFO)
            ->setScope(Connect::RETRIEVE_FINANCIAL_INFO);
        return $connect->getAuthUrl();
    }

}