<?php 
namespace Moip\Magento2\Controller\Notification;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Payment\Transaction\Repository;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Moip\Moip;
use Moip\Auth\BasicAuth;

class Cancel extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
{
	protected $_logger;
	protected $_moipHelper;
	protected $_orderCommentSender;
	protected $resultJsonFactory;

	public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

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
		\Magento\Sales\Model\Order\Email\Sender\OrderCommentSender $orderCommentSender,
		\Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    ) {
    	parent::__construct($context);
    	$this->_resultJsonFactory = $resultJsonFactory;
        $this->_logger = $logger;
		$this->order = $order;
		$this->_order = $_order;
		$this->orderManagement = $orderManagement;
		$this->transaction = $transaction;
		$this->transactionRepository = $transactionRepository;
		$this->_orderFactory = $orderFactory;
		$this->_moipHelper = $moipHelper;
		$this->_orderCommentSender = $orderCommentSender;
        
    }
	
	public function execute()
	{
		
		$moip = $this->_moipHelper->AuthorizationValidate();
		$response = file_get_contents('php://input');
		$originalNotification = json_decode($response, true);
		$authorization = $this->getRequest()->getHeader('Authorization');
		$token = $this->_moipHelper->getInfoUrlPreferenceToken('cancel');
		$this->_logger->debug($token);
		$resultJson = $this->_resultJsonFactory->create();
		if($authorization != $token){
			$this->_logger->debug("Authorization Invalida ".$authorization);
			$this->_logger->debug("Authorization token esperada ".$token);
			return $resultJson->setData(['success' => 0]);
		} else {
			$order_id = $originalNotification['resource']['payment']['_links']['order']['title']; 
			$order = $moip->orders()->get($order_id);
			$transaction_id = $order->getOwnId();
			if($transaction_id){
				$this->_logger->debug("Cancelamento do pagamento do pedido ".$transaction_id);
				$order = $this->order->loadByIncrementId($transaction_id);
				$payment = $order->getPayment();
				$transactionId = $payment->getLastTransId();
				$method = $payment->getMethodInstance();
				try {
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
					if(Order::STATE_CANCELED !== $order->getState()){
						$method->fetchTransactionInfo($payment, $transactionId, $description_for_customer);
						$order->save();
						$this->addCancelDetails($description_for_customer, $order);
					}
				} catch(\Exception $e) {
					return $resultJson->setData(['success' => 0]);
				}
				return $resultJson->setData(['success' => 1]);
			};
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