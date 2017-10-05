<?php
namespace Moip\Magento2\Controller\Adminhtml\System\Config;

use Moip\Moip;

use Magento\Framework\Controller\ResultFactory; 
class Oauth extends \Magento\Backend\App\Action
{

   
    protected $_configInterface;
    
    protected $_storeManager;
    
   
    public function __construct(
        \Moip\Magento2\Helper\Data $moipHelper,
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool,
        \Magento\Framework\App\Config\ConfigResource\ConfigInterface $configInterface,
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        \Moip\Auth\Connect $connect
        
        ) 
    {
        $this->_moipHelper = $moipHelper;
        $this->_cacheTypeList = $cacheTypeList;
        $this->_cacheFrontendPool = $cacheFrontendPool;
        $this->_configInterface = $configInterface;
        $this->_resourceConfig = $resourceConfig;
        $this->_connect = $connect;
        parent::__construct($context);
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Moip_Magento2::oauth');
    }

    public function execute()
    {
        $params = $this->getRequest()->getParams();
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        
        if(isset($params['code'])){
            
            $authorize = $this->getAuthorize($params['code']);

            $oauth     = $authorize->accessToken;
            $this->setOauth($oauth);

            $publickey = $this->getKeyPublic($oauth);
            $this->setKeyPublic($publickey);
            
            if($oauth){
                $this->_cacheTypeList->cleanType("config");
                $resultRedirect->setUrl($this->getUrlPreference());
            }
           
        }

        return $resultRedirect;
    }

    

    private function getUrlPreference()
    {
        return $this->getUrl('moip/system_config/preference');
    }


    private function setKeyPublic($publickey){
        $_environment   = $this->_moipHelper->getEnvironmentMode();
        $this->_resourceConfig->saveConfig(
                    'payment/moipbase/publickey_'.$_environment,
                    $publickey,
                    'default',
                    0
                );
       return $this;
    }

    private function setOauth($oauth){
        $_environment   = $this->_moipHelper->getEnvironmentMode();
        $this->_resourceConfig->saveConfig(
                    'payment/moipbase/oauth_'.$_environment,
                    $oauth,
                    'default',
                    0
                );
       return $this;
    }

    

    private function getAuthorize($code){

        $_environment   = $this->_moipHelper->getEnvironmentMode();

        if($_environment === "production"){
            $redirect_uri   = $this->_moipHelper::REDIRECT_URI_PRODUCTION;
            $client_id      = $this->_moipHelper::APP_ID_PRODUCTION;
            $url            = $this->_connect::ENDPOINT_PRODUCTION;
            $client_secrect = $this->_moipHelper::CLIENT_SECRECT_PRODUCTION;

        } else {
            $redirect_uri   = $this->_moipHelper::REDIRECT_URI_SANDBOX;
            $client_id      = $this->_moipHelper::APP_ID_SANDBOX;
            $url            = $this->_connect::ENDPOINT_SANDBOX;
            $client_secrect = $this->_moipHelper::CLIENT_SECRECT_SANDBOX;
        }
        


        $connect = new $this->_connect($redirect_uri, $client_id, true, $url);
        $connect->setClientSecret($client_secrect);
        $connect->setCode($code);
        $auth = $connect->authorize();
        
        return $auth;

    }

    private function getKeyPublic($oauth) {
            $documento = 'Content-Type: application/json; charset=utf-8';

            $_environment   = $this->_moipHelper->getEnvironmentMode();

            if($_environment === "production"){
                $url   = $this->_moipHelper::URL_KEY_PRODUCTION;
            } else {
                $url   = $this->_moipHelper::URL_KEY_SANDBOX;
            }

            $header = "Authorization: OAuth " . $oauth;

            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [$header, $documento]);
            curl_setopt($ch,CURLOPT_USERAGENT,'MoipMagento2/2.0.0');
            $responseBody = curl_exec($ch);
            curl_close($ch);
            
            $responseBody = json_decode($responseBody, true);
            $publickey = $responseBody['keys']['encryption'];
        return $publickey;
    }

   

}