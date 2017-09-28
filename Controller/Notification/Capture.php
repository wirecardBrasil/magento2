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
			$moip = $this->_moipHelper->AuthorizationValidate();
			$response = file_get_contents('php://input');
			$originalNotification = json_decode($response, true);

			$httpRequestObject = new \Zend_Controller_Request_Http();

			$authorization = $httpRequestObject->getHeader('Authorization');
			
			$token = $this->_moipHelper->getInfoUrlPreferenceToken('capture');
			
			if($authorization != $token){
				return $this;
			} 
			$order_id = $originalNotification['resource']['payment']['_links']['order']['title']; 
			$order = $moip->orders()->get($order_id);
			$transaction_id= $order->getOwnId();
			if($transaction_id){
						$this->_logger->debug($transaction_id);
						$order = $this->order->loadByIncrementId($transaction_id);
						 if($order->canInvoice()) {
								$invoice = $this->_invoiceService->prepareInvoice($order);
								$invoice->setRequestedCaptureCase(Invoice::CAPTURE_ONLINE);
								$invoice->register();
								$invoice->save();
								$transactionSave = $this->_transaction->addObject(
									$invoice
								)->addObject(
									$invoice->getOrder()
								);
								$transactionSave->save();
								$this->invoiceSender->send($invoice);
								//send notification code
								$order->addStatusHistoryComment(
									__('Notified customer about invoice #%1.', $invoice->getId())
								)
								->setIsCustomerNotified(true)
								->save();
						 } else {
						 	$this->_logger->debug("Not canInvoice".$transaction_id);
						 }
						
			}
	}
}