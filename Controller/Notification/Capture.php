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


class Capture extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
{
	protected $_logger;

	protected $_moipHelper;

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
		\Magento\Sales\Model\OrderFactory $orderFactory,
		\Moip\Magento2\Helper\Data $moipHelper,
		\Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
	) {
    	parent::__construct($context);
    	$this->resultJsonFactory = $resultJsonFactory;
        $this->_logger = $logger;
		$this->order = $order;
		$this->_orderFactory = $orderFactory;
		$this->_moipHelper = $moipHelper;
	}
	
	public function execute()
	{

		$resultJson = $this->resultJsonFactory->create();
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
			return $resultJson->setData(['success' => 0]);
		} else {
			$order_id = $originalNotification['resource']['payment']['_links']['order']['title']; 
			$order = $moip->orders()->get($order_id);
			$transaction_id = $order->getOwnId();
			
			if($transaction_id){
				$this->_logger->debug("Autoriza pagamento do pedido ".$transaction_id);
				$order = $this->order->loadByIncrementId($transaction_id);

				$payment = $order->getPayment();
				$transactionId = $payment->getLastTransId();

				$method = $payment->getMethodInstance();
				try {
					$method->fetchTransactionInfo($payment, $transactionId);
					$order->save();
				} catch(\Exception $e) {
					return $resultJson->setData(['success' => 0]);
				}
				return $resultJson->setData(['success' => 1]);
			};
		}
	}
}