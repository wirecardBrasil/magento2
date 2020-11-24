<?php
/**
 * Copyright © Wirecard Brasil. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */

namespace Moip\Magento2\Controller\Webhooks;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\Store\Model\StoreManagerInterface;
use Moip\Magento2\Gateway\Config\Config;

/**
 * Class Accept - Receives communication for payment accepted.
 */
class Accept extends Action implements CsrfAwareActionInterface
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
        StoreManagerInterface $storeManager,
        JsonFactory $resultJsonFactory
    ) {
        parent::__construct($context);
        $this->config = $config;
        $this->logger = $logger;
        $this->orderFactory = $orderFactory;
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
        $resultJson = $this->resultJsonFactory->create();
        $response = $this->getRequest()->getContent();
        $originalNotification = json_decode($response, true);
        $authorization = $this->getRequest()->getHeader('Authorization');
        $storeId = $this->storeManager->getStore()->getId();
        $storeCaptureToken = $this->config->getMerchantGatewayCaptureToken($storeId);
        if ($storeCaptureToken === $authorization) {
            $order = $this->orderFactory->create()->load($originalNotification['id'], 'ext_order_id');
            $this->logger->debug([
                'webhook'            => 'accept',
                'ext_order_id'       => $originalNotification['id'],
                'increment_order_id' => $order->getIncrementId(),
            ]);
            $payment = $order->getPayment();
            if (!$order->getInvoiceCollection()->count()) {
                try {
                    $payment->accept();
                    $payment->save();
                    $order->save();
                } catch (\Exception $e) {
                    return $resultJson->setData([
                        'success' => 0,
                        'error'   => $e,
                    ]);
                }

                return $resultJson->setData([
                    'success' => 1,
                    'status'  => $order->getStatus(),
                    'state'   => $order->getState(),
                ]);
            }

            return $resultJson->setData([
                'success' => 0,
                'error'   => 'The transaction could not be accept',
            ]);
        }

        return $resultJson->setData(['success' => 0]);
    }
}
