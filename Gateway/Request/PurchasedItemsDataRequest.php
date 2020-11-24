<?php
/**
 * Copyright © Wirecard Brasil. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */

namespace Moip\Magento2\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Moip\Magento2\Gateway\Config\Config;
use Moip\Magento2\Gateway\SubjectReader;

/**
 * Class PurchasedItemsDataRequest - Data structure of purchased items.
 */
class PurchasedItemsDataRequest implements BuilderInterface
{
    /**
     * BillingAddress block name.
     */
    const PURCHASED_ITEMS = 'items';

    /**
     * The street address. Maximum 255 characters
     * Required.
     */
    const PURCHASED_ITEM_PRODUCT = 'product';

    /**
     * The street number. 1 or 10 alphanumeric digits
     * Required.
     */
    const PURCHASED_ITEM_QUANTITY = 'quantity';

    /**
     * The district address. Maximum 255 characters
     * Required.
     */
    const PURCHASED_ITEM_DETAIL = 'detail';

    /**
     * The complement address. Maximum 255 characters
     * Required.
     */
    const PURCHASED_ITEM_PRICE = 'price';

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * @var Config
     */
    private $config;

    /**
     * @param SubjectReader $subjectReader
     * @param Config        $config
     */
    public function __construct(
        SubjectReader $subjectReader,
        Config $config
    ) {
        $this->subjectReader = $subjectReader;
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function build(array $buildSubject)
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);

        $result = [];
        $order = $paymentDO->getOrder();
        $items = $order->getItems();
        $itemcount = count($items);
        if ($itemcount) {
            foreach ($items as $item) {
                if ($item->getParentItem()) {
                    continue;
                }
                if ($item->getPrice() == 0) {
                    continue;
                }
                if ($item->getPrice() > 0) {
                    $result[self::PURCHASED_ITEMS][] = [
                        self::PURCHASED_ITEM_PRODUCT  => $item->getName(),
                        self::PURCHASED_ITEM_QUANTITY => $item->getQtyOrdered(),
                        self::PURCHASED_ITEM_DETAIL   => $item->getSku(),
                        self::PURCHASED_ITEM_PRICE    => $this->config->formatPrice($item->getPrice()),
                    ];
                }
            }
        }

        return $result;
    }
}
