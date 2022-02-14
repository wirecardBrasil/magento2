<?php
/**
 * Copyright © Moip by PagSeguro. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Moip\Magento2\Model\Console\Command\Preference;

use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\State;
use Magento\Framework\HTTP\ZendClient;
use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Moip\Magento2\Gateway\Config\Config as MoipConfig;
use Moip\Magento2\Model\Console\Command\AbstractModel;
use Psr\Log\LoggerInterface;

/**
 * Class All Preference Webhook.
 */
class All extends AbstractModel
{
    /**
     * @var State
     */
    private $state;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var MoipConfig
     */
    private $moipConfig;

    /**
     * @var Json
     */
    private $json;

    /**
     * @var ZendClientFactory
     */
    private $httpClientFactory;

    /**
     * All constructor.
     *
     * @param LoggerInterface      $logger
     * @param ScopeConfigInterface $scopeConfig
     * @param State                $state
     * @param MoipConfig           $moipConfig
     * @param Json                 $json
     * @param ZendClientFactory    $httpClientFactory
     */
    public function __construct(
        LoggerInterface $logger,
        ScopeConfigInterface $scopeConfig,
        State $state,
        MoipConfig $moipConfig,
        Json $json,
        ZendClientFactory $httpClientFactory
    ) {
        parent::__construct(
            $logger
        );
        $this->state = $state;
        $this->scopeConfig = $scopeConfig;
        $this->moipConfig = $moipConfig;
        $this->json = $json;
        $this->httpClientFactory = $httpClientFactory;
    }

    /**
     * Command All.
     *
     * @return void
     */
    public function all()
    {
        $this->writeln('List All Preference');
        $preference = $this->getPreferenceWebhooks();
        if (!$preference) {
            $this->writeln(__('<error>Error %1</error>', $preference['error']));

            return $this;
        }

        foreach ($preference['preference'] as $webhooks) {
            if (isset($webhooks['id'])) {
                // phpcs:ignore
                $this->writeln(__('<info>Preference ID: %1 Target Uri: %2</info>', $webhooks['id'], $webhooks['target']));
            }
        }
        $this->writeln(__('Finished'));

        return $this;
    }

    /**
     * List All Preference Webhooks.
     *
     * @return array
     */
    private function getPreferenceWebhooks(): array
    {
        $uri = $this->moipConfig->getApiUrl();
        $apiBearer = $this->moipConfig->getMerchantGatewayOauth();
        $client = $this->httpClientFactory->create();

        $client->setUri($uri.'preferences/notifications');
        $client->setConfig(['maxredirects' => 0, 'timeout' => 30]);
        $client->setHeaders('Authorization', 'Bearer '.$apiBearer);
        $client->setMethod(ZendClient::GET);

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
