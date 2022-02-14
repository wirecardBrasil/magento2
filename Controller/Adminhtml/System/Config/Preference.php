<?php
/**
 * Copyright © Moip by PagSeguro. All rights reserved.
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
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\StoreManagerInterface;
use Moip\Magento2\Gateway\Config\Config as ConfigBase;

/*
 * Class Preference - define webhooks
 */
class Preference extends \Magento\Backend\App\Action
{
    /**
     * @var TypeListInterface
     */
    protected $cacheTypeList;

    /**
     * @var Pool
     */
    protected $cacheFrontendPool;

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var ConfigInterface
     */
    protected $configInterface;

    /**
     * @var Config
     */
    protected $resourceConfig;

    /**
     * @var ConfigBase
     */
    protected $configBase;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ZendClientFactory
     */
    protected $httpClientFactory;

    /**
     * @var Json
     */
    protected $json;

    /**
     * @param Context               $context
     * @param TypeListInterface     $cacheTypeList
     * @param Pool                  $cacheFrontendPool
     * @param JsonFactory           $resultJsonFactory
     * @param ConfigInterface       $configInterface
     * @param Config                $resourceConfig
     * @param ConfigBase            $configBase
     * @param StoreManagerInterface $storeManager
     * @param ZendClientFactory     $httpClientFactory
     * @param Json                  $json
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
        ZendClientFactory $httpClientFactory,
        Json $json
    ) {
        $this->cacheTypeList = $cacheTypeList;
        $this->cacheFrontendPool = $cacheFrontendPool;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->configInterface = $configInterface;
        $this->resourceConfig = $resourceConfig;
        $this->configBase = $configBase;
        $this->storeManager = $storeManager;
        $this->httpClientFactory = $httpClientFactory;
        $this->json = $json;
        parent::__construct($context);
    }

    /**
     * ACL - Is Allowed.
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Moip_Magento2::preference');
    }

    /**
     * Execute.
     *
     * @return json
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

    /**
     * Get Url Config.
     *
     * @return string
     */
    private function getUrlConfig()
    {
        return $this->getUrl('adminhtml/system_config/edit/section/payment/');
    }

    /**
     * Get Url Capture.
     *
     * @return string
     */
    private function getUrlCapture()
    {
        $storeId = $this->storeManager->getDefaultStoreView()->getStoreId();

        return $this->storeManager->getStore($storeId)->getUrl('moip/webhooks/accept');
    }

    /**
     * Get Url Cancel.
     *
     * @return string
     */
    private function getUrlCancel()
    {
        $storeId = $this->storeManager->getDefaultStoreView()->getStoreId();

        return $this->storeManager->getStore($storeId)->getUrl('moip/webhooks/deny');
    }

    /**
     * Get Url Refund.
     *
     * @return string
     */
    private function getUrlRefund()
    {
        $storeId = $this->storeManager->getDefaultStoreView()->getStoreId();

        return $this->storeManager->getStore($storeId)->getUrl('moip/webhooks/refund');
    }

    /**
     * Set Url Info Refund.
     *
     * @param string $webhook
     *
     * @return void
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

    /**
     * Set Url Info Cancel.
     *
     * @param string $webhook
     *
     * @return void
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

    /**
     * Set Url Info Capture.
     *
     * @param string $webhook
     *
     * @return void
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

    /**
     * Set Url Webook Refund.
     *
     * @param string $url
     *
     * @return void
     */
    private function setUrlWebhookRefund($url)
    {
        $webhook = [
            'events' => ['REFUND.COMPLETED', 'REFUND.FAILED'],
            'target' => $url,
            'media'  => 'WEBHOOK',
        ];

        return $this->setWebhooks($webhook);
    }

    /**
     * Set Url Webook Cancel.
     *
     * @param string $url
     *
     * @return void
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

    /**
     * Set Url Webook Capture.
     *
     * @param string $url
     *
     * @return void
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

    /**
     * Set Webhooks.
     *
     * @param string $webhook
     *
     * @return void
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
        $client->setRawData($this->json->serialize($webhook), 'application/json');
        $client->setMethod(ZendClient::POST);

        $result = $client->request()->getBody();

        return $this->json->unserialize($result);
    }
}
