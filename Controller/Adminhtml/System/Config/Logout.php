<?php
namespace Moip\Magento2\Controller\Adminhtml\System\Config;

use Moip\Moip;
use Moip\Auth\Connect;

class Logout extends \Magento\Backend\App\Action
{

    protected $resultJsonFactory;

    protected $_configInterface;
    
    protected $_storeManager;
    
   
    public function __construct(
        \Moip\Magento2\Helper\Data $moipHelper,
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\App\Config\ConfigResource\ConfigInterface $configInterface,
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager
        
        ) 
    {
        $this->_moipHelper = $moipHelper;
        $this->_cacheTypeList = $cacheTypeList;
        $this->_cacheFrontendPool = $cacheFrontendPool;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->_configInterface = $configInterface;
        $this->_resourceConfig = $resourceConfig;
        $this->_storeManager = $storeManager;
        parent::__construct($context);
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Moip_Magento2::logout');
    }

    public function execute()
    {
        $params = $this->getRequest()->getParams();
        $resultJson = $this->resultJsonFactory->create();
        $this->_cacheTypeList->cleanType("config");
        
        $type_url       = ['cancel','capture','refund'];
            
        foreach ($type_url as $_type_url) {
            $id = $this->_moipHelper->getInfoUrlPreferenceInfo($_type_url);
            if($id){
               
                try {
                    $moip           = $this->_moipHelper->AuthorizationValidate();
                    $this->urlDeleteNotication($id);
                }
                catch(\Exception $e) {
                   
                }
                $this->setClearUrlInfo($_type_url);    
            }
            
        }
        $this->setClearOauth();
        $this->_cacheTypeList->cleanType("config");
        return $resultJson->setData([
            'messages' => 'Successfully.',
            'error' => false
        ]);
    }

    

    private function urlDeleteNotication($id){
            $moip           = $this->_moipHelper->AuthorizationValidate();
            try {
                $moip->notifications()->delete($id); 
            } catch (Exception $e) {
                 return $this;
            }
            
            
        return $this;
    }

    private function setClearUrlInfo($type_url){

        $_environment   = $this->_moipHelper->getEnvironmentMode();
        $this->_resourceConfig->deleteConfig(
                    'payment/moipbase/'.$type_url.'_id_'.$_environment,
                    'default',
                    0
                );
        $this->_resourceConfig->deleteConfig(
                    'payment/moipbase/'.$type_url.'_token_'.$_environment,
                    'default',
                    0
                );
        return $this;
    }

    private function setClearOauth(){
        $_environment   = $this->_moipHelper->getEnvironmentMode();

        $this->_resourceConfig->deleteConfig(
                    'payment/moipbase/oauth_'.$_environment,
                    'default',
                    0
                );
        $this->_resourceConfig->deleteConfig(
                    'payment/moipbase/publickey_'.$_environment,
                    'default',
                    0
                );
        return $this;
    }

}