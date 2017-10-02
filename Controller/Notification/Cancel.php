<?php 
namespace Moip\Magento2\Controller\Notification;
use Magento\Sales\Model\Order;
use Magento\Sales\Api\OrderManagementInterface;
use Moip\Moip;
use Moip\Auth\BasicAuth;
class Cancel extends \Magento\Framework\App\Action\Action
{	
	protected $_logger;
	protected $_moipHelper;
	
	public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Psr\Log\LoggerInterface $logger,
		 \Magento\Sales\Api\Data\OrderInterface $order,
		 OrderManagementInterface $orderManagement,
		 \Moip\Magento2\Helper\Data $moipHelper
    ) {
        $this->_logger = $logger;
		$this->order = $order;
		$this->orderManagement = $orderManagement;
		$this->_moipHelper = $moipHelper;
        parent::__construct($context);
    }
	
	public function execute()
	{
			
			$moip = $this->_moipHelper->AuthorizationValidate();
			$response = file_get_contents('php://input');
			$originalNotification = json_decode($response, true);
			$this->_logger->debug($response);

			$httpRequestObject = new \Zend_Controller_Request_Http();
			$authorization = $httpRequestObject->getHeader('Authorization');
			
			$token = $this->_moipHelper->getInfoUrlPreferenceToken('cancel');
			
			if($authorization != $token){
				$this->_logger->debug("Authorization Invalida ".$authorization);
				return $this;
			} 
			$order_id = $originalNotification['resource']['payment']['_links']['order']['title'];
			  $order = $moip->orders()->get($order_id);
			  $transaction_id= $order->getOwnId();
		 	if($transaction_id){
				$order = $this->order->loadByIncrementId($transaction_id);
				$this->orderManagement->cancel($order->getEntityId());
			}
	}
}