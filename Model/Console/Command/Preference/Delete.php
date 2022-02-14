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
 * Class Delete Preference Webhook.
 */
class Delete extends AbstractModel
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
     * @var ZendClientFactory
     */
    private $httpClientFactory;

    /**
     * @var Json
     */
    private $json;

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
     * Command Delete.
     *
     * @param array $ids
     *
     * @return void
     */
    public function delete($ids = [])
    {
        $this->writeln('Delete Preference');
        foreach ($ids as $id) {
            $preference = $this->deleteWebhook($id);
            if (!$preference) {
                $this->writeln(__('<error>error deleting %1: %2</error>', $id, $preference['error']));

                return $this;
            }
            $this->writeln(__('<info>Deleted preference %1</info>', $id));
        }
        $this->writeln(__('Finished'));

        return $this;
    }

    /**
     * Delete Preference Webhooks.
     *
     * @param string $id
     *
     * @return array
     */
    private function deleteWebhook(string $id): array
    {
        $uri = $this->moipConfig->getApiUrl();
        $apiBearer = $this->moipConfig->getMerchantGatewayOauth();
        $client = $this->httpClientFactory->create();

        $client->setUri($uri.'preferences/notifications/'.$id);
        $client->setConfig(['maxredirects' => 0, 'timeout' => 30]);
        $client->setHeaders('Authorization', 'Bearer '.$apiBearer);
        $client->setMethod(ZendClient::DELETE);

        try {
            $status = $client->request()->getStatus();

            return [
                'success' => true,
                'status'  => $status,
            ];
        } catch (Exception $e) {
            return ['success' => true, 'error' =>  $e->getMessage()];
        }
    }
}
