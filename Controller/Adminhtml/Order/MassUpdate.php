<?php
/**
 * Copyright © Moip by PagSeguro. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */

namespace Moip\Magento2\Controller\Adminhtml\Order;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Ui\Component\MassAction\Filter;

/**
 * Class Mass Update - Fetch Payment Review Update.
 */
class MassUpdate extends \Magento\Sales\Controller\Adminhtml\Order\AbstractMassAction
{
    /**
     * @var OrderManagementInterface
     */
    protected $orderManagement;

    /**
     * @param Context                  $context
     * @param Filter                   $filter
     * @param CollectionFactory        $collectionFactory
     * @param OrderManagementInterface $orderManagement
     * @param Order                    $order
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        OrderManagementInterface $orderManagement,
        Order $order
    ) {
        parent::__construct($context, $filter);
        $this->collectionFactory = $collectionFactory;
        $this->orderManagement = $orderManagement;
        $this->order = $order;
    }

    /**
     * Mass Action.
     *
     * @param AbstractCollection $collection
     *
     * @return resultRedirectFactory
     */
    protected function massAction(AbstractCollection $collection)
    {
        $countUpdate = 0;
        $countNotUpdate = 0;

        foreach ($collection->getItems() as $order) {
            if (!$order->getEntityId()) {
                continue;
            }

            $loadedOrder = $this->order->load($order->getEntityId());

            if ($loadedOrder->canFetchPaymentReviewUpdate()) {
                $payment = $loadedOrder->getPayment();
                $transactionId = $payment->getLastTransId();
                $method = $payment->getMethodInstance();

                try {
                    $method->fetchTransactionInfo($payment, $transactionId);
                    $payment->getOrder()->save();
                    $state = $payment->getOrder()->getState();
                    if ($state === 'processing') {
                        $countUpdate++;
                    }
                    $countNotUpdate++;
                } catch (\Exception $exc) {
                    $countNotUpdate++;
                }
            } elseif (!$loadedOrder->canFetchPaymentReviewUpdate()) {
                $countNotUpdate++;
            }

            continue;
        }

        if ($countUpdate) {
            $this->messageManager->addSuccess(__('%1 payments have been updated.', $countUpdate));
        }
        if ($countNotUpdate) {
            $this->messageManager->addWarning(__('%1 payments have not yet been updated.', $countNotUpdate));
        }

        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath($this->getComponentRefererUrl());

        return $resultRedirect;
    }
}
