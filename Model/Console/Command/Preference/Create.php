<?php
/**
 * Copyright © Wirecard Brasil. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Moip\Magento2\Model\Console\Command\Preference;

use Exception;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\State;
use Magento\Framework\HTTP\ZendClient;
use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Url\Validator;
use Moip\Magento2\Gateway\Config\Config as MoipConfig;
use Moip\Magento2\Model\Console\Command\AbstractModel;
use Psr\Log\LoggerInterface;

/**
 * Class Create Preference Webhook.
 */
class Create extends AbstractModel
{
    /**
     * State.
     *
     * @var \Magento\Framework\App\State
     */
    private $state;

    /**
     * ScopeConfigInterface.
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Config.
     *
     * @var \Magento\Config\Model\ResourceModel\Config
     */
    private $config;

    /**
     * moipConfig.
     *
     * @var Moip\Magento2\Gateway\Config\Config
     */
    private $moipConfig;

    /**
     * Validator.
     *
     * @var \Magento\Framework\Url\Validator
     */
    private $validator;

    /**
     * ZendClientFactory.
     *
     * @var \Magento\Framework\HTTP\ZendClientFactory
     */
    private $httpClientFactory;

    /**
     * Json.
     *
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $json;

    /**
     * Create constructor.
     *
     * @param LoggerInterface      $logger
     * @param ScopeConfigInterface $scopeConfig
     * @param State                $state
     * @param MoipConfig           $moipConfig
     * @param Config               $config
     * @param Json                 $json
     * @param ZendClientFactory    $httpClientFactory
     */
    public function __construct(
        LoggerInterface $logger,
        ScopeConfigInterface $scopeConfig,
        State $state,
        MoipConfig $moipConfig,
        Config $config,
        Validator $validator,
        Json $json,
        ZendClientFactory $httpClientFactory
    ) {
        parent::__construct(
            $logger
        );
        $this->state = $state;
        $this->scopeConfig = $scopeConfig;
        $this->moipConfig = $moipConfig;
        $this->config = $config;
        $this->validator = $validator;
        $this->json = $json;
        $this->httpClientFactory = $httpClientFactory;
    }

    public function preference(string $baseUrl)
    {
        $this->writeln('Init Set Preference');
        $this->writeln(__('<info>Setting preferences for the domain: %1</info>', $baseUrl));

        $valid = $this->validator->isValid($baseUrl);
        if (!$valid) {
            $this->writeln(__('<error>The URL entered is invalid %1, it must contain https://...</error>', $baseUrl));

            return $this;
        }

        $formatValid = str_ends_with($baseUrl, '/');
        if (!$formatValid) {
            $this->writeln(__("<error>Your url %1 is valid, but must end with '/'</error>", $baseUrl));

            return $this;
        }

        $this->createPreference($baseUrl, 'accept');
        $this->createPreference($baseUrl, 'deny');
        $this->createPreference($baseUrl, 'refund');
        $this->writeln(__('Finished'));

        return $this;
    }

    /**
     * Create Preference.
     *
     * @param $baseUrl
     * @param $type
     *
     * @return array
     */
    private function createPreference(string $baseUrl, string $type)
    {
        $data = $this->createWebhookData($baseUrl, $type);
        $create = $this->sendPreference($data);

        if ($create['success']) {
            $preference = $create['preference'];
            if (isset($preference['id'])) {
                $this->writeln(__('<info>Your preference for method %1 has been successfully created: %2</info>', $type, $preference['id']));
                $registryConfig = $this->setConfigPreferenceInfo($preference, $type);

                if (!$registryConfig) {
                    $this->writeln(__('<error>Error saving information in database: %1</error>', $registryConfig['error']));
                }
            } elseif (isset($preference['code'])) {
                $this->writeln(__('<error>Error creating preference %1: %2</error>', $type, $preference['description']));
            }
        } else {
            $this->writeln(__('<error>Error creating preference %1: %2</error>', $type, $create['error']));
        }

        return $this;
    }

    /**
     * Set Config Preference Info.
     *
     * @param $baseUrl
     * @param $type
     *
     * @return array
     */
    private function setConfigPreferenceInfo(array $data, string $type): array
    {
        $environment = $this->moipConfig->getEnvironmentMode();
        $pathPattern = 'payment/moip_magento2/%s_%s_%s';

        if ($type === 'accept') {
            $pathConfigId = sprintf($pathPattern, 'capture', 'id', $environment);
            $pathConfigToken = sprintf($pathPattern, 'capture', 'token', $environment);
        } elseif ($type === 'deny') {
            $pathConfigId = sprintf($pathPattern, 'cancel', 'id', $environment);
            $pathConfigToken = sprintf($pathPattern, 'cancel', 'token', $environment);
        } elseif ($type === 'refund') {
            $pathConfigId = sprintf($pathPattern, 'refund', 'id', $environment);
            $pathConfigToken = sprintf($pathPattern, 'refund', 'token', $environment);
        }

        try {
            $this->config->saveConfig(
                $pathConfigId,
                $data['id'],
                'default',
                0
            );
            $this->resourceConfig->saveConfig(
                $pathConfigToken,
                $data['token'],
                'default',
                0
            );
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }

        return ['success' => true];
    }

    /**
     * Create Url For Accept.
     *
     * @return string
     */
    private function createUrlForAccept($baseUrl): string
    {
        return $baseUrl.'moip/webhooks/accept';
    }

    /**
     * Create Url For Deny.
     *
     * @return string
     */
    private function createUrlForDeny($baseUrl): string
    {
        return $baseUrl.'moip/webhooks/deny';
    }

    /**
     * Create Url For Refund.
     *
     * @return string
     */
    private function createUrlForRefund($baseUrl): string
    {
        return $baseUrl.'moip/webhooks/refund';
    }

    /**
     * Create Webhook Data.
     *
     * @param $baseUrl
     * @param $type
     *
     * @return array
     */
    private function createWebhookData(string $baseUrl, string $type): array
    {
        if ($type === 'accept') {
            $event = ['ORDER.PAID'];
            $url = $this->createUrlForAccept($baseUrl);
        } elseif ($type === 'deny') {
            $event = ['ORDER.NOT_PAID'];
            $url = $this->createUrlForDeny($baseUrl);
        } elseif ($type === 'refund') {
            $event = ['REFUND.COMPLETED', 'REFUND.FAILED'];
            $url = $this->createUrlForRefund($baseUrl);
        }

        $webhook = [
            'events' => $event,
            'target' => $url,
            'media'  => 'WEBHOOK',
        ];

        return $webhook;
    }

    /*
     * Set Webhooks
     *
     * @param $webhook
     * @return array
     */
    private function sendPreference($data): array
    {
        $uri = $this->moipConfig->getApiUrl();
        $apiBearer = $this->moipConfig->getMerchantGatewayOauth();
        $client = $this->httpClientFactory->create();
        $dataSend = $this->json->serialize($data);

        $client->setUri($uri.'preferences/notifications');
        $client->setConfig(['maxredirects' => 0, 'timeout' => 30]);
        $client->setHeaders('Authorization', 'Bearer '.$apiBearer);
        $client->setRawData($dataSend, 'application/json');
        $client->setMethod(ZendClient::POST);

        try {
            $result = $client->request()->getBody();

            return [
                'success'    => true,
                'preference' => $this->json->unserialize($result),
            ];
        } catch (Exception $e) {
            return ['success' => true, 'error' =>  $e->getMessage()];
        }
    }
}
