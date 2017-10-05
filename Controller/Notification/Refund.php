<?php 
namespace Moip\Magento2\Controller\Notification;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Api\OrderManagementInterface;
use Moip\Moip;
use Moip\Auth\BasicAuth;
class Refund extends \Magento\Framework\App\Action\Action
{	
	protected $_logger;
	protected $_moipHelper;
	
	public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Psr\Log\LoggerInterface $logger,
		 \Magento\Sales\Api\Data\OrderInterface $order,
		 OrderManagementInterface $orderManagement,
		 \Magento\Sales\Model\Order\CreditmemoFactory $creditmemoFactory,
		 \Magento\Sales\Model\Order\Invoice $Invoice,
		\Magento\Sales\Model\Service\CreditmemoService $CreditmemoService,
		\Moip\Magento2\Helper\Data $moipHelper
    ) {
		$this->_logger = $logger;
		$this->order = $order;
		$this->orderManagement = $orderManagement;
		$this->creditmemoFactory = $creditmemoFactory;
        $this->CreditmemoService = $CreditmemoService;
        $this->Invoice = $Invoice;
		$this->_moipHelper = $moipHelper;
		parent::__construct($context);
    }

	public function execute()
	{
		
			$moip = $this->_moipHelper->AuthorizationValidate();
			$response = file_get_contents('php://input');
			$originalNotification = json_decode($response, true);
			$this->_logger->debug($response);

			$authorization = $this->getRequest()->getHeader('Authorization');
			
			$token = $this->_moipHelper->getInfoUrlPreferenceToken('refund');
			
			if($authorization != $token){

				return $this;
			} 
			
			$order_id = $originalNotification['resource']['refund']['_links']['order']['title'];
			

			$order = $moip->orders()->get($order_id);
			$transaction_id= $order->getOwnId();
			if($transaction_id){
				$order = $this->order->loadByIncrementId($transaction_id);
				$invoices = $order->getInvoiceCollection();
				if($invoices){
					foreach($invoices as $invoice){
						$invoiceincrementid = $invoice->getIncrementId();
					}
					$invoiceobj =  $this->Invoice->loadByIncrementId($invoiceincrementid);
					$creditmemo = $this->creditmemoFactory->createByOrder($order);
					
					$creditmemo->setInvoice($invoiceobj);
					$this->CreditmemoService->refund($creditmemo); 
			 	}
			}	
	}
}