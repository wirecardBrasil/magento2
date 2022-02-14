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
 * Class FetchTransactionInfoClient - Returns order query.
 */
class FetchTransactionInfoClient implements ClientInterface
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
            $client->setUri($url.'orders/'.$orderMoip);
            $client->setConfig(['maxredirects' => 0, 'timeout' => 45000]);
            $client->setHeaders('Authorization', 'Bearer '.$apiBearer);
            $client->setMethod(ZendClient::GET);

            $responseBody = $client->request()->getBody();
            $data = $this->json->unserialize($responseBody);
            if (isset($data['status'])) {
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
                $response = array_merge(
                    [
                        'RESULT_CODE'                   => 1,
                        'STATUS'                        => $data['status'],
                        'CANCELLATION_DETAILS_CUSTOMER' => $cancelDetailsCus,
                        'CANCELLATION_DETAILS_ADMIN'    => $cancelDetailsAdmin,
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
                    'url'      => $url.'orders/'.$orderMoip,
                    'response' => $responseBody,
                ]
            );
        } catch (InvalidArgumentException $e) {
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new \Exception('Invalid JSON was returned by the gateway');
        }

        return $response;
    }
}
