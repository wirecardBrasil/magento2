<?php
/**
 * Copyright © Moip by PagSeguro. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Moip\Magento2\Controller\Webhooks;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\CsrfAwareActionInterface as Csrf;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Service\CreditmemoService;
use Magento\Store\Model\StoreManagerInterface;
use Moip\Magento2\Gateway\Config\Config;

/**
 * Class Accept - Receives communication for accept payment.
 */
class Accept extends Action implements Csrf
{
    /**
     * Create Csrf Validation Exception.
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
     * Validate For Csrf.
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
     * @var Config
     */
    protected $config;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var OrderInterfaceFactory
     */
    protected $orderFactory;

    /**
     * @var CreditmemoFactory
     */
    protected $creditmemoFactory;

    /**
     * @var CreditmemoService
     */
    protected $creditmemoService;

    /**
     * @var Invoice
     */
    protected $invoice;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var Json
     */
    protected $json;

    /**
     * @var OrderCommentSender
     */
    protected $orderCommentSender;

    /**
     * @param Context               $context
     * @param Logger                $logger
     * @param Config                $config
     * @param OrderInterfaceFactory $orderFactory
     * @param CreditmemoFactory     $creditmemoFactory
     * @param CreditmemoService     $creditmemoService
     * @param Invoice               $invoice
     * @param StoreManagerInterface $storeManager
     * @param JsonFactory           $resultJsonFactory
     * @param Json                  $json
     */
    public function __construct(
        Context $context,
        Logger $logger,
        Config $config,
        OrderInterfaceFactory $orderFactory,
        CreditmemoFactory $creditmemoFactory,
        CreditmemoService $creditmemoService,
        Invoice $invoice,
        StoreManagerInterface $storeManager,
        JsonFactory $resultJsonFactory,
        Json $json
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
        $this->json = $json;
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
        $originalNotification = $this->json->unserialize($response);
        $authorization = $this->getRequest()->getHeader('Authorization');
        $storeId = $this->storeManager->getStore()->getId();
        $storeCaptureToken = $this->config->getMerchantGatewayCaptureToken($storeId);
        if ($storeCaptureToken === $authorization) {
            $data = $originalNotification['resource']['order'];
            $order = $this->orderFactory->create()->load($data['id'], 'ext_order_id');

            if (!$order->getId()) {
                $resultPage->setHttpResponseCode(406);

                return $resultPage->setJsonData(
                    $this->json->serialize([
                        'error'   => 400,
                        'message' => __('Can not find this order'),
                    ])
                );
            }

            $this->logger->debug([
                'webhook'            => 'accept',
                'ext_order_id'       => $data['id'],
                'increment_order_id' => $order->getIncrementId(),
                'webhook_data'       => $response,
            ]);
            $payment = $order->getPayment();
            if (!$order->getInvoiceCollection()->count() && $order->isPaymentReview()) {
                try {
                    $isOnline = true;
                    $payment->accept($isOnline);
                    $payment->save();
                    $order->save();
                } catch (\Exception $exc) {
                    $resultPage->setHttpResponseCode(500);
                    $resultPage->setJsonData(
                        $this->json->serialize([
                            'error'   => 400,
                            'message' => $exc->getMessage(),
                        ])
                    );
                }

                return $resultPage->setJsonData(
                    $this->json->serialize([
                        'success'   => 1,
                        'status'    => $order->getStatus(),
                        'state'     => $order->getState(),
                    ])
                );
            }

            $resultPage->setHttpResponseCode(400);

            return $resultPage->setJsonData(
                $this->json->serialize([
                    'error'   => 400,
                    'message' => 'The transaction could not be refund',
                ])
            );
        }
        $resultPage->setHttpResponseCode(401);

        return $resultPage;
    }
}
