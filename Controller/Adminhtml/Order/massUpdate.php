<?php
namespace Moip\Magento2\Controller\Adminhtml\Order;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Sales\Api\OrderManagementInterface;

/**
 * Class MassDelete
 */
class MassUpdate extends \Magento\Sales\Controller\Adminhtml\Order\AbstractMassAction
{
    
    protected $orderManagement;

    protected $_moipHelper;

    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        OrderManagementInterface $orderManagement,
        \Moip\Magento2\Helper\Data $moipHelper
    ) {
        parent::__construct($context, $filter);
        $this->collectionFactory = $collectionFactory;
        $this->orderManagement = $orderManagement;
        $this->_moipHelper = $moipHelper;
    }

   
    protected function massAction(AbstractCollection $collection)
    {
        $countUpdateOrder = 0;
        $countUpdateOrderWaiting = 0;
        $model = $this->_objectManager->create('Magento\Sales\Model\Order');
        foreach ($collection->getItems() as $order) {
            if (!$order->getEntityId()) {
                continue;
            }
            $loadedOrder = $model->load($order->getEntityId());
          
            if ($loadedOrder->canFetchPaymentReviewUpdate()) {
                $payment = $loadedOrder->getPayment();
                $transactionId = $payment->getLastTransId();
                $method = $payment->getMethodInstance();
                try {
                    $method->fetchTransactionInfo($payment, $transactionId);
                    $payment->getOrder()->save();
                    $countUpdateOrder++;
                } catch(\Exception $e) {
                    $countUpdateOrderWaiting++;
                }
             } else {
                $countUpdateOrderWaiting++;
             }
        
            
        }
        

        if ($countUpdateOrder) {
            $this->messageManager->addSuccess(__('%1 pagamentos foram atualizados.', $countUpdateOrder));
        }
        if($countUpdateOrderWaiting){
            $this->messageManager->addWarning(__('%1 pagamentos ainda não tem atualização.', $countUpdateOrderWaiting));
        }

        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath($this->getComponentRefererUrl());
        return $resultRedirect;
    }
}

?>