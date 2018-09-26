<?php 
namespace Moip\Magento2\Controller\Notification;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Api\OrderManagementInterface;
use Moip\Moip;
use Moip\Auth\BasicAuth;
class Capture extends \Magento\Framework\App\Action\Action
{	
	protected $_logger;
	protected $_moipHelper;
	
	public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Psr\Log\LoggerInterface $logger,
		 \Magento\Sales\Api\Data\OrderInterface $order,
		 OrderManagementInterface $orderManagement,
		 \Magento\Sales\Model\Service\InvoiceService $invoiceService,
		  \Magento\Framework\DB\Transaction $transaction,
		  \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
		  \Moip\Magento2\Helper\Data $moipHelper
		 
    ) {
		$this->_invoiceService = $invoiceService;
        $this->_transaction = $transaction;
        $this->_logger = $logger;
		$this->order = $order;
		$this->orderManagement = $orderManagement;
		$this->invoiceSender = $invoiceSender;
		$this->_moipHelper = $moipHelper;
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
			
			if($authorization != $token){
				$this->_logger->debug("Authorization Invalida ".$authorization);
				return $this;
			} 
			
			$order_id = $originalNotification['resource']['payment']['_links']['order']['title']; 
			$order = $moip->orders()->get($order_id);
			$transaction_id = $order->getOwnId();
			if($transaction_id){
						$this->_logger->debug($transaction_id);
						$order = $this->order->loadByIncrementId($transaction_id);
						$order->getPayment()->capture(null);
						$order->save();
						
			}
	}
}