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
	protected $_orderCommentSender;
	
	public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Psr\Log\LoggerInterface $logger,
		 \Magento\Sales\Api\Data\OrderInterface $order,
		 OrderManagementInterface $orderManagement,
		 \Moip\Magento2\Helper\Data $moipHelper,
		 \Magento\Sales\Model\Order\Email\Sender\OrderCommentSender $orderCommentSender
    ) {
        $this->_logger = $logger;
		$this->order = $order;
		$this->orderManagement = $orderManagement;
		$this->_moipHelper = $moipHelper;
		$this->_orderCommentSender = $orderCommentSender;
        parent::__construct($context);
    }
	
	public function execute()
	{
			
			
			$moip = $this->_moipHelper->AuthorizationValidate();
			$response = file_get_contents('php://input');
			$originalNotification = json_decode($response, true);
			/*$this->_logger->debug($response);*/

			$authorization = $this->getRequest()->getHeader('Authorization');
			
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
				
				try {
					if(isset($originalNotification['resource']['payment']['cancellationDetails'])){
						$description_cancel = $originalNotification['resource']['payment']['cancellationDetails']['description'];
						$description = __($description_cancel);
						$this->addCancelDetails($description, $order, $this->orderManagement);
						
					} else {
						$description_cancel = "Prazo limite excedido";
						$description = __($description_cancel);
						$this->addCancelDetails($description, $order, $this->orderManagement);
					}
				} catch(\Exception $e) {
		            throw new \Magento\Framework\Exception\LocalizedException(__('Payment not update ' . $e->getMessage()));
		        }
				
			}

			

	}

	private function addCancelDetails($comment, $order){
		$status = $this->orderManagement->getStatus($order->getEntityId());
		$history = $order->addStatusHistoryComment($comment, $status);
	    $history->setIsVisibleOnFront(1);
	    $history->setIsCustomerNotified(1);
	    $history->save();
	    $comment = trim(strip_tags($comment));
	    $order->save();
	    $this->_orderCommentSender->send($order, 1, $comment);
	    return $this;
	}
}