<?php
/**
 * Copyright © Wirecard Brasil. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Moip\Magento2\Controller\Adminhtml\System\Config;

use Magento\Backend\App\Action\Context;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\App\Cache\Frontend\Pool;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ConfigResource\ConfigInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\HTTP\ZendClient;
use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Store\Model\StoreManagerInterface;
use Moip\Magento2\Gateway\Config\Config as ConfigBase;

/*
 * Class Preferenc
 */
class Preference extends \Magento\Backend\App\Action
{
    /*
     * @var cacheTypeList
     */
    protected $cacheTypeList;

    /*
     * @var cacheFrontendPool
     */
    protected $cacheFrontendPool;

    /*
     * @var resultJsonFactory
     */
    protected $resultJsonFactory;

    /*
     * @var configInterface
     */
    protected $configInterface;

    /*
     * @var configBase
     */
    protected $configBase;

    /*
     * @var resourceConfig
     */
    protected $resourceConfig;

    /*
     * @var storeManager
     */
    protected $storeManager;

    /*
     * @param Context
     * @param TypeListInterface
     * @param Pool
     * @param JsonFactory
     * @param ConfigInterface
     * @param Config
     * @param ConfigBase
     * @param StoreManagerInterface
     */
    public function __construct(
        Context $context,
        TypeListInterface $cacheTypeList,
        Pool $cacheFrontendPool,
        JsonFactory $resultJsonFactory,
        ConfigInterface $configInterface,
        Config $resourceConfig,
        ConfigBase $configBase,
        StoreManagerInterface $storeManager,
        ZendClientFactory $httpClientFactory
    ) {
        $this->cacheTypeList = $cacheTypeList;
        $this->cacheFrontendPool = $cacheFrontendPool;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->configInterface = $configInterface;
        $this->resourceConfig = $resourceConfig;
        $this->configBase = $configBase;
        $this->storeManager = $storeManager;
        $this->httpClientFactory = $httpClientFactory;
        parent::__construct($context);
    }

    /*
     * Is Allowed
     *
     * @return boolean
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Moip_Magento2::preference');
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        $captureUrl = $this->getUrlCapture();
        $webhookCapture = $this->setUrlWebhookCapture($captureUrl);
        if (isset($webhookCapture['code'])) {
            if ($webhookCapture['code'] === 'API-009') {
                $this->messageManager->addError(
                    __('Your module is authorized, but you have reached the notification url preference limit.')
                );
                $this->cacheTypeList->cleanType('config');
                $resultRedirect->setUrl($this->getUrlConfig());

                return $resultRedirect;
            }
        }
        $this->setUrlInfoCapture($webhookCapture);

        $cancelUrl = $this->getUrlCancel();
        $webhookCancel = $this->setUrlWebhookCancel($cancelUrl);
        if (isset($webhookCancel['code'])) {
            if ($webhookCancel['code'] === 'API-009') {
                $this->messageManager->addError(
                    __('Your module is authorized, but you have reached the notification url preference limit.')
                );
                $this->cacheTypeList->cleanType('config');
                $resultRedirect->setUrl($this->getUrlConfig());

                return $resultRedirect;
            }
        }

        $this->setUrlInfoCancel($webhookCancel);

        $refundUrl = $this->getUrlRefund();
        $webhookRefund = $this->setUrlWebhookRefund($refundUrl);
        if (isset($webhookRefund['code'])) {
            if ($webhookRefund['code'] === 'API-009') {
                $this->messageManager->addError(
                    __('Your module is authorized, but you have reached the notification url preference limit.')
                );
                $this->cacheTypeList->cleanType('config');
                $resultRedirect->setUrl($this->getUrlConfig());

                return $resultRedirect;
            }
        }
        $this->setUrlInfoRefund($webhookRefund);

        $this->messageManager->addSuccess(__('Your module is authorized. =)'));
        $this->cacheTypeList->cleanType('config');
        $resultRedirect->setUrl($this->getUrlConfig());

        return $resultRedirect;
    }

    /*
     * Url Config
     *
     * @return string
     */
    private function getUrlConfig()
    {
        return $this->getUrl('adminhtml/system_config/edit/section/payment/');
    }

    /*
     * Url Capture
     *
     * @return string
     */
    private function getUrlCapture()
    {
        $storeId = $this->storeManager->getDefaultStoreView()->getStoreId();

        return $this->storeManager->getStore($storeId)->getUrl('moip/webhooks/capture');
    }

    /*
     * Url Cancel
     *
     * @return string
     */
    private function getUrlCancel()
    {
        $storeId = $this->storeManager->getDefaultStoreView()->getStoreId();

        return $this->storeManager->getStore($storeId)->getUrl('moip/webhooks/cancel');
    }

    /*
     * Url Refund
     *
     * @return string
     */
    private function getUrlRefund()
    {
        $storeId = $this->storeManager->getDefaultStoreView()->getStoreId();

        return $this->storeManager->getStore($storeId)->getUrl('moip/webhooks/refund');
    }

    /*
     * Set Url Info Refund
     */
    private function setUrlInfoRefund($webhook)
    {
        $environment = $this->configBase->getEnvironmentMode();

        $this->resourceConfig->saveConfig(
            'payment/moip_magento2/refund_id_'.$environment,
            $webhook['id'],
            'default',
            0
        );

        $this->resourceConfig->saveConfig(
            'payment/moip_magento2/refund_token_'.$environment,
            $webhook['token'],
            'default',
            0
        );

        return $this;
    }

    /*
     * Set Url Info Cancel
     */
    private function setUrlInfoCancel($webhook)
    {
        $environment = $this->configBase->getEnvironmentMode();

        $this->resourceConfig->saveConfig(
            'payment/moip_magento2/cancel_id_'.$environment,
            $webhook['id'],
            'default',
            0
        );

        $this->resourceConfig->saveConfig(
            'payment/moip_magento2/cancel_token_'.$environment,
            $webhook['token'],
            'default',
            0
        );

        return $this;
    }

    /*
     * Set Url Info Capture
     */
    private function setUrlInfoCapture($webhook)
    {
        $environment = $this->configBase->getEnvironmentMode();

        $this->resourceConfig->saveConfig(
            'payment/moip_magento2/capture_id_'.$environment,
            $webhook['id'],
            'default',
            0
        );

        $this->resourceConfig->saveConfig(
            'payment/moip_magento2/capture_token_'.$environment,
            $webhook['token'],
            'default',
            0
        );

        return $this;
    }

    /*
     * Set Url Webhook Refund
     */
    private function setUrlWebhookRefund($url)
    {
        $webhook = [
            'events' => ['REFUND.REQUESTED'],
            'target' => $url,
            'media'  => 'WEBHOOK',
        ];

        return $this->setWebhooks($webhook);
    }

    /*
     * Set Url Webhook Cancel
     */
    private function setUrlWebhookCancel($url)
    {
        $webhook = [
            'events' => ['ORDER.NOT_PAID'],
            'target' => $url,
            'media'  => 'WEBHOOK',
        ];

        return $this->setWebhooks($webhook);
    }

    /*
     * Set Url Webhook Capture
     */
    private function setUrlWebhookCapture($url)
    {
        $webhook = [
            'events' => ['ORDER.PAID'],
            'target' => $url,
            'media'  => 'WEBHOOK',
        ];

        return $this->setWebhooks($webhook);
    }

    /*
     * Set Webhooks
     *
     * @param $webhook
     *
     * @return array
     */
    private function setWebhooks($webhook)
    {
        $params = $this->getRequest()->getParams();
        $url = ConfigBase::ENDPOINT_PREFERENCES_PRODUCTION;
        $environment = $this->configBase->getEnvironmentMode();
        if ($environment === ConfigBase::ENVIRONMENT_SANDBOX) {
            $url = ConfigBase::ENDPOINT_PREFERENCES_SANDBOX;
        }
        $apiBearer = $params['oauth'];

        $client = $this->httpClientFactory->create();

        $client->setUri($url);
        $client->setConfig(['maxredirects' => 0, 'timeout' => 30]);
        $client->setHeaders('Authorization', 'Bearer '.$apiBearer);
        $client->setRawData(json_encode($webhook), 'application/json');
        $client->setMethod(ZendClient::POST);

        $result = $client->request()->getBody();

        return json_decode($result, true);
    }
}
