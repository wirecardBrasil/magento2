<?php
namespace Moip\Magento2\Controller\Adminhtml\System\Config;

use Moip\Moip;
use Moip\Auth\Connect;
use Magento\Framework\Controller\ResultFactory;
class Preference extends \Magento\Backend\App\Action
{

    protected $resultJsonFactory;

    protected $_configInterface;
    
    protected $_storeManager;
    
   
    public function __construct(
        \Moip\Magento2\Helper\Data $moipHelper,
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool,
        \Magento\Framework\App\Config\ConfigResource\ConfigInterface $configInterface,
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager
        
        ) 
    {
        $this->_moipHelper = $moipHelper;
        $this->_cacheTypeList = $cacheTypeList;
        $this->_cacheFrontendPool = $cacheFrontendPool;
        $this->_configInterface = $configInterface;
        $this->_resourceConfig = $resourceConfig;
        $this->_storeManager = $storeManager;
        parent::__construct($context);
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Moip_Magento2::preference');
    }

    public function execute()
    {
        $params = $this->getRequest()->getParams();
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $this->_cacheTypeList->cleanType("config");
        $moip           = $this->_moipHelper->AuthorizationValidate();
        
        
            $url_refund = $this->urlNoticationRefunded($moip);
            $this->setUrlInfoRefund($url_refund);

            $url_cancel = $this->urlNoticationCancel($moip);
            $this->setUrlInfoCancel($url_cancel);

            $url_capture = $this->urlNoticationCapture($moip);
            $this->setUrlInfoCapture($url_capture);
            
        
        $this->messageManager->addSuccess(__('Seu módulo está autorizado. =)'));
        $this->_cacheTypeList->cleanType("config");
        $resultRedirect->setUrl($this->getUrlConfig());
        return $resultRedirect;
    }

    private function getUrlConfig()
    {
        return $this->getUrl('adminhtml/system_config/edit/section/payment/');
    }

    private function setUrlInfoRefund($url_refund){

        $_environment   = $this->_moipHelper->getEnvironmentMode();
        $this->_resourceConfig->saveConfig(
                    'payment/moipbase/refund_id_'.$_environment,
                    $url_refund->getId(),
                    'default',
                    0
                );
        $this->_resourceConfig->saveConfig(
                    'payment/moipbase/refund_token_'.$_environment,
                    $url_refund->getToken(),
                    'default',
                    0
                );
       return $this;
    }

    private function setUrlInfoCancel($url_cancel){

        $_environment   = $this->_moipHelper->getEnvironmentMode();
        $this->_resourceConfig->saveConfig(
                    'payment/moipbase/cancel_id_'.$_environment,
                    $url_cancel->getId(),
                    'default',
                    0
                );
        $this->_resourceConfig->saveConfig(
                    'payment/moipbase/cancel_token_'.$_environment,
                    $url_cancel->getToken(),
                    'default',
                    0
                );
       return $this;
    }

    private function setUrlInfoCapture($url_capture){

        $_environment   = $this->_moipHelper->getEnvironmentMode();
        $this->_resourceConfig->saveConfig(
                    'payment/moipbase/capture_id_'.$_environment,
                    $url_capture->getId(),
                    'default',
                    0
                );
        $this->_resourceConfig->saveConfig(
                    'payment/moipbase/capture_token_'.$_environment,
                    $url_capture->getToken(),
                    'default',
                    0
                );
       return $this;
    }

    

    private function urlNoticationRefunded($moip){
        
        $domainName     = $this->_storeManager->getStore()->getBaseUrl();

        $webhooks = $moip->notifications()
            ->addEvent('REFUND.REQUESTED')
            ->setTarget($domainName.'moip/notification/Refund')
            ->create();
        return $webhooks;
    }

    private function urlNoticationCancel($moip){
       
        $domainName     = $this->_storeManager->getStore()->getBaseUrl();

        $webhooks = $moip->notifications()
            ->addEvent('PAYMENT.CANCELLED')
            ->setTarget($domainName.'moip/notification/Cancel')
            ->create();
        return $webhooks;
    }

    private function urlNoticationCapture($moip){
        
        $domainName     = $this->_storeManager->getStore()->getBaseUrl();

        $webhooks = $moip->notifications()
            ->addEvent('PAYMENT.AUTHORIZED')
            ->setTarget($domainName.'moip/notification/Capture')
            ->create();
        return $webhooks;
    }

}