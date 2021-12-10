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
use Magento\Sales\Model\Order\Email\Sender\OrderCommentSender;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Service\CreditmemoService;
use Magento\Store\Model\StoreManagerInterface;
use Moip\Magento2\Gateway\Config\Config;

/**
 * Class Deny - Receives communication for deny payment.
 */
class Deny extends Action implements Csrf
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
     * @param Invoice               $invoice
     * @param StoreManagerInterface $storeManager
     * @param JsonFactory           $resultJsonFactory
     * @param Json                  $json
     * @param OrderCommentSender    $orderCommentSender
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
        JsonFactory $resultJsonFactory,
        Json $json,
        OrderCommentSender $orderCommentSender
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
        $this->orderCommentSender = $orderCommentSender;
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
        $storeCaptureToken = $this->config->getMerchantGatewayCancelToken($storeId);
        if ($storeCaptureToken === $authorization) {

            $data = $originalNotification['resource']['order'];
            $order = $this->orderFactory->create()->load($data['id'], 'ext_order_id');

            if(!$order->getId()) {
                $resultPage->setHttpResponseCode(406);
                return $resultPage->setJsonData(
                    $this->json->serialize([
                        'error' => 400,
                        'message' => __('Can not find this order'),
                    ])
                );
            }

            $this->logger->debug([
                'webhook'            => 'deny',
                'ext_order_id'       => $data['id'],
                'increment_order_id' => $order->getIncrementId(),
                'webhook_data'       => $response,
            ]);
            $payment = $order->getPayment();
            if ($order->canVoidPayment()) {
                try {
                    $isOnline = true;
                    $payment->void($isOnline);
                    $payment->save();
                    $cancelDetailsAdmin = __('We did not record the payment.');
                    $cancelDetailsCus = __('The payment deadline has been exceeded.');
                    if (isset($data['payments'])) {
                        foreach ($data['payments'] as $payment) {
                            if (isset($payment['cancellationDetails'])) {
                                $cancelCode = $payment['cancellationDetails']['code'];
                                $cancelDescription = $payment['cancellationDetails']['description'];
                                $cancelBy = $payment['cancellationDetails']['cancelledBy'];
                                $cancelDetailsAdmin = __('%1, code %2, by %3', $cancelDescription, $cancelCode, $cancelBy);
                                $cancelDetailsCus = __('%1', $cancelDescription);
                            }
                        }
                    }
                    /** customer information for cancel **/
                    $history = $order->addStatusHistoryComment($cancelDetailsCus);
                    $history->setIsVisibleOnFront(1);
                    $history->setIsCustomerNotified(1);
                    // $order->sendOrderUpdateEmail(1, $cancelDetailsCus);

                    /** admin information for cancel **/
                    $history = $order->addStatusHistoryComment($cancelDetailsAdmin);
                    $history->setIsVisibleOnFront(0);
                    $history->setIsCustomerNotified(0);
                    $order->save();

                    $this->orderCommentSender->send($order, 1, $cancelDetailsCus);
                } catch (\Exception $exc) {
                    $resultPage->setHttpResponseCode(500);
                    return $resultPage->setJsonData(
                        $this->json->serialize([
                            'error' => 400,
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

            $resultPage->setHttpResponseCode(201);

            return $resultPage->setJsonData(
                $this->json->serialize([
                    'error'   => 400,
                    'message' => 'The transaction could not be cancel',
                ])
            );
        }

        $resultPage->setHttpResponseCode(401);

        return $resultPage;
    }
}
