<?php
/**
 * Copyright © Moip by PagSeguro. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Moip\Magento2\Controller\Webhooks;

use Exception;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\CsrfAwareActionInterface as Crsf;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Service\CreditmemoService;
use Magento\Store\Model\StoreManagerInterface;
use Moip\Magento2\Gateway\Config\Config;

/**
 * Class Refund - Receives communication for refunded payment.
 */
class Refund extends Action implements Crsf
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
     * @return bool
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
     * @var CreditmemoRepositoryInterface
     */
    private $creditmemoRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $schCriteriaBuilder;

    /**
     * @param Context                       $context
     * @param Logger                        $logger
     * @param Config                        $config
     * @param OrderInterfaceFactory         $orderFactory
     * @param CreditmemoFactory             $creditmemoFactory
     * @param CreditmemoService             $creditmemoService
     * @param Invoice                       $invoice
     * @param StoreManagerInterface         $storeManager
     * @param JsonFactory                   $resultJsonFactory
     * @param Json                          $json
     * @param CreditmemoRepositoryInterface $creditmemoRepository
     * @param SearchCriteriaBuilder         $schCriteriaBuilder
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
        Json $json,
        CreditmemoRepositoryInterface $creditmemoRepository,
        SearchCriteriaBuilder $schCriteriaBuilder
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
        $this->creditmemoRepository = $creditmemoRepository;
        $this->schCriteriaBuilder = $schCriteriaBuilder;
    }

    /**
     * Command Refund.
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
        $storeCaptureToken = $this->config->getMerchantGatewayRefundToken($storeId);

        if ($storeCaptureToken === $authorization) {
            $resource = $originalNotification['resource'];
            $extRefundId = $resource['refund']['id'];
            $extStatus = $resource['refund']['status'];

            $creditmemos = $this->getCreditMemoByTransactionId($extRefundId);
            if (count($creditmemos)) {
                foreach ($creditmemos as $creditmemo) {
                    if ($extStatus === 'REQUESTED') {
                        $creditmemo->setState(Creditmemo::STATE_OPEN);
                    } elseif ($extStatus === 'COMPLETED') {
                        $creditmemo->setState(Creditmemo::STATE_REFUNDED);
                    } elseif ($extStatus === 'FAILED') {
                        $creditmemo->setState(Creditmemo::STATE_CANCELED);
                    }

                    try {
                        $creditmemo->save();
                    } catch (\Exception $exc) {
                        $resultPage->setHttpResponseCode(500);
                        $resultPage->setJsonData(
                            $this->json->serialize([
                                'error'   => 400,
                                'message' => $exc->getMessage(),
                            ])
                        );
                    }

                    continue;
                }
            } else {
                $extOrderId = $resource['refund']['_links']['order']['title'];
                $newCreditmemo = $this->createNewCreditMemo($extOrderId, $extRefundId);
                if ($newCreditmemo) {
                    $creditmemo = $newCreditmemo;
                    if ($extStatus === 'REQUESTED') {
                        $creditmemo->setState(Creditmemo::STATE_OPEN);
                    } elseif ($extStatus === 'COMPLETED') {
                        $creditmemo->setState(Creditmemo::STATE_REFUNDED);
                    } elseif ($extStatus === 'FAILED') {
                        $creditmemo->setState(Creditmemo::STATE_CANCELED);
                    }

                    try {
                        $this->creditmemoService->refund($creditmemo);
                    } catch (\Exception $exc) {
                        $resultPage->setHttpResponseCode(500);
                        $resultPage->setJsonData(
                            $this->json->serialize([
                                'error'   => 400,
                                'message' => $exc->getMessage(),
                            ])
                        );
                    }
                } else {
                    return $resultPage->setJsonData(
                        $this->json->serialize([
                            'error'   => 404,
                            'message' => 'The transaction could not be refund',
                        ])
                    );
                }
            }

            return $resultPage->setJsonData(
                $this->json->serialize([
                    'success'    => 1,
                    'extOrderId' => $extRefundId,
                    'state'      => $creditmemo->getState(),
                ])
            );
        }

        $resultPage->setHttpResponseCode(401);

        return $resultPage;
    }

    /**
     * Get Creditmemo.
     *
     * @param string $transactionId
     *
     * @return creditmemo
     */
    public function getCreditMemoByTransactionId(string $transactionId)
    {
        $searchCriteria = $this->schCriteriaBuilder
            ->addFilter('transaction_id', $transactionId)->create();

        try {
            $creditmemos = $this->creditmemoRepository->getList($searchCriteria);
            $creditmemoRecords = $creditmemos->getItems();
        } catch (Exception $exception) {
            $this->logger->critical($exception->getMessage());
            $creditmemoRecords = null;
        }

        return $creditmemoRecords;
    }

    /**
     * Create new creditmemo.
     *
     * @param string $extOrderId
     * @param string $extRefundId
     *
     * @return creditmemo
     */
    public function createNewCreditMemo(string $extOrderId, string $extRefundId)
    {
        $order = $this->orderFactory->create()->load($extOrderId, 'ext_order_id');
        $creditmemo = null;

        $payment = $order->getPayment();
        $invoices = $order->getInvoiceCollection();

        if ($invoices) {
            foreach ($invoices as $invoiceLoad) {
                $invoiceincrementid = $invoiceLoad->getIncrementId();
            }
            $invoiceobj = $this->invoice->loadByIncrementId($invoiceincrementid);
            $creditmemo = $this->creditmemoFactory->createByOrder($order);
            $payment->setTransactionId($extRefundId);
            $creditmemo->setInvoice($invoiceobj);
        }

        return $creditmemo;
    }
}
