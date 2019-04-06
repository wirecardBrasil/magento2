<?php 
namespace Moip\Magento2\Controller\Notification;
use Magento\Sales\Model\Order;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Payment\Transaction\Repository;
use Magento\Sales\Model\OrderFactory;
use Moip\Moip;
use Moip\Auth\BasicAuth;

class Capture extends \Magento\Framework\App\Action\Action
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

		
			$this->_logger->debug("entrou na capture");

			$moip = $this->_moipHelper->AuthorizationValidate();
			$response = file_get_contents('php://input');
			$originalNotification = json_decode($response, true);
			$this->_logger->debug($response);

			$authorization = $this->getRequest()->getHeader('Authorization');
			
			$token = $this->_moipHelper->getInfoUrlPreferenceToken('capture');
			$this->_logger->debug($token);
			if($authorization != $token){
				$this->_logger->debug("Authorization Invalida ".$authorization);
				return $this;
			} 
			
			$order_id = $originalNotification['resource']['payment']['_links']['order']['title']; 
			$order = $moip->orders()->get($order_id);
			$transaction_id = $order->getOwnId();
			if($transaction_id){
						$this->_logger->debug("Autoriza pagamento do pedido ".$transaction_id);
						$order = $this->order->loadByIncrementId($transaction_id);
						$payment = $order->getPayment();
						$payment->setIsTransactionApproved(true)->save();
    					$payment->capture(null)->save();
			}
			return $this;
	}
}