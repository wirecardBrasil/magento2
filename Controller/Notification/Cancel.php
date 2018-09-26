<?php 
namespace Moip\Magento2\Controller\Notification;
use Magento\Sales\Model\Order;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Payment\Transaction\Repository;
use Magento\Sales\Model\OrderFactory;
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
		\Magento\Sales\Model\Order $_order,
		\Magento\Sales\Api\OrderManagementInterface $orderManagement,
		\Magento\Sales\Model\Order\Payment\Transaction $transaction,
		\Magento\Sales\Model\Order\Payment\Transaction\Repository $transactionRepository,
		\Magento\Sales\Model\OrderFactory $orderFactory,
		\Moip\Magento2\Helper\Data $moipHelper,
		\Magento\Sales\Model\Order\Email\Sender\OrderCommentSender $orderCommentSender
    ) {
        $this->_logger = $logger;
		$this->order = $order;
		$this->_order = $_order;
		$this->orderManagement = $orderManagement;
		$this->transaction = $transaction;
		$this->transactionRepository = $transactionRepository;
		$this->_orderFactory = $orderFactory;
		$this->_moipHelper = $moipHelper;
		$this->_orderCommentSender = $orderCommentSender;
        parent::__construct($context);
    }
	
	public function execute()
	{
		$moip = $this->_moipHelper->AuthorizationValidate();
		$response = file_get_contents('php://input');
		$originalNotification = json_decode($response, true);
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
			if($order){
				try{
					if(isset($originalNotification['resource']['payment']['cancellationDetails'])){

						$description_by = $originalNotification['resource']['payment']['cancellationDetails']['cancelledBy'];
						$description_code = $originalNotification['resource']['payment']['cancellationDetails']['code'];
						$description_description = $originalNotification['resource']['payment']['cancellationDetails']['description'];

						$description_for_customer = __($description_description);
						$description_for_store = sprintf(__('Pedido cancelado por %s, código do cancelamento %s, motivo: %s'), $description_by, $description_code, $description_description );
					}else{
						$description_cancel = "Prazo limite de pagamento excedido";
						$description_for_store = "Motivo indefinido";
						$description_for_customer = __($description_cancel);
					}
					
					$order->registerCancellation($description_for_store);
					$order->save();
					$this->addCancelDetails($description_for_customer, $order);
					$this->_logger->info("Order Cancel Successfully with Id ".$order->getId());						
				}catch(\Exception $e){
					throw new \Magento\Framework\Exception\LocalizedException(__('Payment not update ' . $e->getMessage()));
				}		
			}else{
				$this->_logger->info("Invalide Order Id");
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