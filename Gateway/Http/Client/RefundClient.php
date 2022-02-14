<?php
/**
 * Copyright © Moip by PagSeguro. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Moip\Magento2\Gateway\Http\Client;

use InvalidArgumentException;
use Magento\Framework\HTTP\ZendClient;
use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;
use Moip\Magento2\Gateway\Config\Config;

/**
 * Class RefundClient - Returns refund query.
 */
class RefundClient implements ClientInterface
{
    /**
     * @var string
     */
    public const MOIP_ORDER_ID = 'moip_order_id';

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ZendClientFactory
     */
    private $httpClientFactory;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Json
     */
    private $json;

    /**
     * @param Logger            $logger
     * @param ZendClientFactory $httpClientFactory
     * @param Config            $config
     * @param Json              $json
     */
    public function __construct(
        Logger $logger,
        ZendClientFactory $httpClientFactory,
        Config $config,
        Json $json
    ) {
        $this->config = $config;
        $this->httpClientFactory = $httpClientFactory;
        $this->logger = $logger;
        $this->json = $json;
    }

    /**
     * Places request to gateway.
     *
     * @param TransferInterface $transferObject
     *
     * @return array
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $client = $this->httpClientFactory->create();
        $request = $transferObject->getBody();

        $url = $this->config->getApiUrl();
        $apiBearer = $this->config->getMerchantGatewayOauth();
        $orderMoip = $request[self::MOIP_ORDER_ID];

        try {
            $client->setUri($url.'orders/'.$orderMoip.'/refunds');
            $client->setConfig(['maxredirects' => 0, 'timeout' => 45000]);
            $client->setHeaders('Authorization', 'Bearer '.$apiBearer);
            $client->setRawData($this->json->serialize($request['send']), 'application/json');
            $client->setMethod(ZendClient::POST);

            $responseBody = $client->request()->getBody();

            $data = $this->json->unserialize($responseBody);

            if (isset($data['id'])) {
                $response = array_merge(
                    [
                        'RESULT_CODE'   => 1,
                        'STATUS'        => $data['status'],
                        'REFUND_ID'     => $data['id'],
                    ],
                    $data
                );
            } else {
                $response = array_merge(
                    [
                        'RESULT_CODE' => 0,
                    ],
                    $data
                );
            }
            $this->logger->debug(
                [
                    'url'      => $url.'orders/'.$orderMoip.'/refunds',
                    'request'  => $this->json->serialize($request['send']),
                    'response' => $responseBody,
                ]
            );
        } catch (InvalidArgumentException $e) {
            $this->logger->debug(
                [
                    'url'      => $url.'orders/'.$orderMoip.'/refunds',
                    'request'  => $this->json->serialize($request['send']),
                    'response' => $responseBody,
                ]
            );
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new \Exception('Invalid JSON was returned by the gateway');
        }

        return $response;
    }
}
