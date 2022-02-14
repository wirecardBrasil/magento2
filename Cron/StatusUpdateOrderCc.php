<?php
/**
 * Copyright © Moip by PagSeguro. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */

namespace Moip\Magento2\Cron;

use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Moip\Magento2\Gateway\Config\ConfigCc;

/*
 * Class StatusUpdateOrderCc - Cron fetch order
 */
class StatusUpdateOrderCc
{
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var Order
     */
    protected $order;

    /**
     * @var ConfigCc
     */
    protected $configCc;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @param Order             $order
     * @param Logger            $logger
     * @param ConfigCc          $configCc
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        Order $order,
        Logger $logger,
        ConfigCc $configCc,
        CollectionFactory $collectionFactory
    ) {
        $this->order = $order;
        $this->logger = $logger;
        $this->configCc = $configCc;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Execute.
     *
     * @return void
     */
    public function execute()
    {
        $orders = $this->collectionFactory->create()
        ->addFieldToFilter('status', [
            'in' => [
                Order::STATE_PAYMENT_REVIEW,
            ],
        ]);

        $orders->getSelect()
                ->join(
                    ['sop' => 'sales_order_payment'],
                    'main_table.entity_id = sop.parent_id',
                    ['method']
                )
                ->where('sop.method = ?', ConfigCc::METHOD);

        foreach ($orders as $order) {
            if (!$order->getEntityId()) {
                continue;
            }
            $loadedOrder = $this->order->load($order->getEntityId());

            if ($loadedOrder->canFetchPaymentReviewUpdate()) {
                $payment = $loadedOrder->getPayment();

                try {
                    $payment->update(true);
                    $loadedOrder->save();
                    $this->logger->debug([
                        'cron'      => 'cc',
                        'type'      => 'updateStatus',
                        'order'     => $loadedOrder->getIncrementId(),
                        'new_state' => $loadedOrder->getStatus(),
                    ]);
                } catch (\Exception $exc) {
                    $this->logger->debug([
                        'cron'  => 'cc',
                        'type'  => 'updateStatus',
                        'order' => $loadedOrder->getIncrementId(),
                        'error' => $exc->getMessage(),
                    ]);
                }
            }
        }
    }
}
