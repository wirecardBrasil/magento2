<?php
/**
 * Copyright © Moip by PagSeguro. All rights reserved.
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
    public const PURCHASED_ITEMS = 'items';

    /**
     * The street address. Maximum 255 characters
     * Required.
     */
    public const PURCHASED_ITEM_PRODUCT = 'product';

    /**
     * The street number. 1 or 10 alphanumeric digits
     * Required.
     */
    public const PURCHASED_ITEM_QUANTITY = 'quantity';

    /**
     * The district address. Maximum 255 characters
     * Required.
     */
    public const PURCHASED_ITEM_DETAIL = 'detail';

    /**
     * The complement address. Maximum 255 characters
     * Required.
     */
    public const PURCHASED_ITEM_PRICE = 'price';

    /**
     * The Category Moip
     * Optional.
     */
    public const PURCHASED_ITEM_CATEGORY = 'category';

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
     * Build.
     *
     * @param array $buildSubject
     */
    public function build(array $buildSubject)
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);

        $result = [];
        $order = $paymentDO->getOrder();
        $storeId = $order->getStoreId();
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
                        self::PURCHASED_ITEM_CATEGORY => $this->config->getMoipCategory($storeId),
                    ];
                }
            }
        }

        return $result;
    }
}
