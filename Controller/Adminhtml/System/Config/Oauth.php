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
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\HTTP\ZendClient;
use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\StoreManagerInterface;
use Moip\Magento2\Gateway\Config\Config as ConfigBase;

/**
 * Class oAuth - Create Authorization.
 */
class Oauth extends \Magento\Backend\App\Action
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
     * @var EncryptorInterface
     */
    protected $encryptor;

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
     * @param EncryptorInterface    $encryptor
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
        EncryptorInterface $encryptor,
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
        $this->encryptor = $encryptor;
        $this->httpClientFactory = $httpClientFactory;
        $this->json = $json;
        parent::__construct($context);
    }

    /**
     * ACL - Check is Allowed.
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Moip_Magento2::oauth');
    }

    /**
     * Excecute.
     *
     * @return json
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $oauth = null;
        if (isset($params['code'])) {
            $oauthResponse = $this->getAuthorize($params['code']);
            if ($oauthResponse) {
                $oauthResponse = $this->json->unserialize($oauthResponse);
                if (isset($oauthResponse['access_token'])) {
                    $oauth = $oauthResponse['access_token'];
                    $this->setOauth($oauth);
                }
                if ($oauth) {
                    $keyPublic = $this->getKeyPublic($oauth);
                    $this->setKeyPublic($keyPublic);
                    $this->setMpa($oauthResponse['moipAccount']['id']);
                    $this->cacheTypeList->cleanType('config');
                    $resultRedirect->setUrl($this->getUrlPreference($oauth));

                    return $resultRedirect;
                }
            }
        }

        $this->messageManager->addError(__('Unable to get the code, try again. =('));
        $resultRedirect->setUrl($this->getUrlConfig());

        return $resultRedirect;
    }

    /**
     * Get Url.
     *
     * @return string
     */
    private function getUrlConfig()
    {
        return $this->getUrl('adminhtml/system_config/edit/section/payment/');
    }

    /**
     * Get Url Preference.
     *
     * @param string $oauth
     *
     * @return string
     */
    private function getUrlPreference($oauth)
    {
        return $this->getUrl('moip/system_config/preference', ['oauth' => $oauth]);
    }

    /**
     * Set MPA.
     *
     * @param string $mpa
     *
     * @return void
     */
    private function setMpa($mpa)
    {
        $environment = $this->configBase->getEnvironmentMode();
        $this->resourceConfig->saveConfig(
            'payment/moip_magento2/mpa_'.$environment,
            $mpa,
            'default',
            0
        );

        return $this;
    }

    /**
     * Set Key Public.
     *
     * @param string $keyPublic
     *
     * @return void
     */
    private function setKeyPublic($keyPublic)
    {
        $environment = $this->configBase->getEnvironmentMode();
        $keyPublic = $this->encryptor->encrypt($keyPublic);
        $this->resourceConfig->saveConfig(
            'payment/moip_magento2/key_public_'.$environment,
            $keyPublic,
            'default',
            0
        );

        return $this;
    }

    /**
     * Set oAuth.
     *
     * @param string $oauth
     *
     * @return string
     */
    private function setOauth($oauth)
    {
        $environment = $this->configBase->getEnvironmentMode();
        $oauth = $this->encryptor->encrypt($oauth);
        $this->resourceConfig->saveConfig(
            'payment/moip_magento2/oauth_'.$environment,
            $oauth,
            'default',
            0
        );

        return $this;
    }

    /**
     * Get Authorize.
     *
     * @param string $code
     *
     * @return json
     */
    private function getAuthorize($code)
    {
        $url = ConfigBase::ENDPOINT_OAUTH_TOKEN_PRODUCTION;
        $arrayToQuery = [
            'client_id'     => ConfigBase::APP_ID_PRODUCTION,
            'client_secret' => ConfigBase::CLIENT_SECRECT_PRODUCTION,
            'redirect_uri'  => ConfigBase::OAUTH_URI,
            'grant_type'    => 'authorization_code',
            'code'          => $code,
        ];

        $environment = $this->configBase->getEnvironmentMode();
        if ($environment === ConfigBase::ENVIRONMENT_SANDBOX) {
            $url = ConfigBase::ENDPOINT_OAUTH_TOKEN_SANDBOX;
            $arrayToQuery = [
                'client_id'     => ConfigBase::APP_ID_SANDBOX,
                'client_secret' => ConfigBase::CLIENT_SECRECT_SANDBOX,
                'redirect_uri'  => ConfigBase::OAUTH_URI,
                'grant_type'    => 'authorization_code',
                'code'          => $code,
            ];
        }

        $client = $this->httpClientFactory->create();

        $client->setUri($url);
        $client->setConfig(['maxredirects' => 0, 'timeout' => 30]);
        $client->setParameterPost($arrayToQuery);
        $client->setMethod(ZendClient::POST);

        $result = $client->request()->getBody();

        return $result;
    }

    /**
     * Get Key Public.
     *
     * @param string $oauth
     *
     * @return string
     */
    private function getKeyPublic($oauth)
    {
        $url = ConfigBase::URL_KEY_PRODUCTION;
        $environment = $this->configBase->getEnvironmentMode();
        if ($environment === ConfigBase::ENVIRONMENT_SANDBOX) {
            $url = ConfigBase::URL_KEY_SANDBOX;
        }
        $header = 'Authorization: OAuth '.$oauth;

        $client = $this->httpClientFactory->create();

        $client->setUri($url);
        $client->setConfig(['maxredirects' => 0, 'timeout' => 30]);
        $client->setHeaders($header);
        $client->setMethod(ZendClient::GET);
        $result = $client->request()->getBody();
        $result = $this->json->unserialize($result);

        return $result['keys']['encryption'];
    }
}
