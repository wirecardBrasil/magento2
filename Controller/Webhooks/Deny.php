<?php
/**
 * Copyright © Wirecard Brasil. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Moip\Magento2\Controller\Webhooks;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Service\CreditmemoService;
use Magento\Store\Model\StoreManagerInterface;
use Moip\Magento2\Gateway\Config\Config;

/**
 * Class Refund - Receives communication for refunded payment.
 */
class Deny extends Action implements CsrfAwareActionInterface
{
    /**
     * createCsrfValidationException.
     *
     * @param RequestInterface $request
     *
     * @return null
     */
    public function createCsrfValidationException(RequestInterface $request): InvalidRequestException
    {
        if ($request) {
            return null;
        }
    }

    /**
     * validateForCsrf.
     *
     * @param RequestInterface $request
     *
     * @return bool true
     */
    public function validateForCsrf(RequestInterface $request): bool
    {
        if ($request) {
            return true;
        }
    }

    /**
     * @var logger
     */
    protected $logger;

    /**
     * @var orderFactory
     */
    protected $orderFactory;

    /**
     * @var resultJsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var storeManager
     */
    protected $storeManager;

    /**
     * @param Context               $context
     * @param logger                $logger
     * @param Config                $config
     * @param OrderInterfaceFactory $orderFactory
     * @param JsonFactory           $resultJsonFactory
     */
    public function __construct(
        Context $context,
        Config $config,
        Logger $logger,
        OrderInterfaceFactory $orderFactory,
        CreditmemoFactory $creditmemoFactory,
        CreditmemoService $creditmemoService,
        Invoice $invoice,
        StoreManagerInterface $storeManager,
        JsonFactory $resultJsonFactory
    ) {
        parent::__construct($context);
        $this->config = $config;
        $this->logger = $logger;
        $this->orderFactory = $orderFactory;
        $this->creditmemoFactory = $creditmemoFactory;
        $this->creditmemoService = $creditmemoService;
        $this->invoice = $invoice;
        $this->storeManager = $storeManager;
        $this->resultJsonFactory = $resultJsonFactory;
    }

    /**
     * Command Accept.
     *
     * @return json
     */
    public function execute()
    {
        if (!$this->getRequest()->isPost()) {
            $resultPage = $this->resultJsonFactory->create();
            $resultPage->setHttpResponseCode(404);

            return $resultPage;
        }

        $resultPage = $this->resultJsonFactory->create();
        $response = $this->getRequest()->getContent();
        $originalNotification = json_decode($response, true);
        $authorization = $this->getRequest()->getHeader('Authorization');
        $storeId = $this->storeManager->getStore()->getId();
        $storeCaptureToken = $this->config->getMerchantGatewayCancelToken($storeId);
        if ($storeCaptureToken === $authorization) {
            $order = $this->orderFactory->create()->load($originalNotification['id'], 'ext_order_id');
            $this->logger->debug([
                'webhook'            => 'deny',
                'ext_order_id'       => $originalNotification['id'],
                'increment_order_id' => $order->getIncrementId(),
            ]);
            $payment = $order->getPayment();
            if (!$order->canCancel()) {
                try {
                    $payment->deny();
                    $payment->save();
                    $order->save();
                } catch (\Exception $exc) {
                    $resultPage->setHttpResponseCode(500);
                    $resultPage->setJsonData(
                        json_encode([
                            'error'   => 400,
                            'message' => $exc->getMessage(),
                        ])
                    );
                }

                return $resultPage->setJsonData(
                    json_encode([
                        'success'   => 1,
                        'status'    => $order->getStatus(),
                        'state'     => $order->getState(),
                    ])
                );
            }

            $resultPage->setHttpResponseCode(201);

            return $resultPage->setJsonData(
                json_encode([
                    'error'   => 400,
                    'message' => 'The transaction could not be refund',
                ])
            );
        }
        $resultPage->setHttpResponseCode(401);

        return $resultPage;
    }
}
