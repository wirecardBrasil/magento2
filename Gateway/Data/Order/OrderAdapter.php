<?php
/**
 * Copyright © Moip by PagSeguro. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */

namespace Moip\Magento2\Gateway\Data\Order;

use Magento\Payment\Gateway\Data\AddressAdapterInterface;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Sales\Model\Order;

/**
 * Class OrderAdapter - Adds necessary information to the order.
 */
class OrderAdapter implements OrderAdapterInterface
{
    /**
     * @var Order
     */
    private $order;

    /**
     * @var AddressAdapter
     */
    private $addAdapterFactory;

    /**
     * @param Order          $order
     * @param AddressAdapter $addAdapterFactory
     */
    public function __construct(
        Order $order,
        AddressAdapterFactory $addAdapterFactory
    ) {
        $this->order = $order;
        $this->addAdapterFactory = $addAdapterFactory;
    }

    /**
     * Returns currency code.
     *
     * @return string
     */
    public function getCurrencyCode()
    {
        return $this->order->getBaseCurrencyCode();
    }

    /**
     * Returns order increment id.
     *
     * @return string
     */
    public function getOrderIncrementId()
    {
        return $this->order->getIncrementId();
    }

    /**
     * Returns customer ID.
     *
     * @return int|null
     */
    public function getCustomerId()
    {
        return $this->order->getCustomerId();
    }

    /**
     * Returns billing address.
     *
     * @return AddressAdapterInterface|null
     */
    public function getBillingAddress()
    {
        if ($this->order->getBillingAddress()) {
            return $this->addAdapterFactory->create(
                ['address' => $this->order->getBillingAddress()]
            );
        }

        return null;
    }

    /**
     * Returns shipping address.
     *
     * @return AddressAdapterInterface|null
     */
    public function getShippingAddress()
    {
        if ($this->order->getShippingAddress()) {
            return $this->addAdapterFactory->create(
                ['address' => $this->order->getShippingAddress()]
            );
        }

        return null;
    }

    /**
     * Returns order store id.
     *
     * @return int
     */
    public function getStoreId()
    {
        return $this->order->getStoreId();
    }

    /**
     * Returns order id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->order->getEntityId();
    }

    /**
     * Returns order grand total amount.
     *
     * @return float|null
     */
    public function getGrandTotalAmount()
    {
        return $this->order->getBaseGrandTotal();
    }

    /**
     * Returns list of line items in the cart.
     *
     * @return \Magento\Sales\Api\Data\OrderItemInterface[]
     */
    public function getItems()
    {
        return $this->order->getItems();
    }

    /**
     * Gets the remote IP address for the order.
     *
     * @return string|null Remote IP address.
     */
    public function getRemoteIp()
    {
        return $this->order->getRemoteIp();
    }

    /**
     * Gets the Dob for the customer.
     *
     * @return string.
     */
    public function getCustomerDob()
    {
        return $this->order->getCustomerDob();
    }

    /**
     * Gets the Tax/Vat for the customer.
     *
     * @return string|null Tax/Vat.
     */
    public function getCustomerTaxvat()
    {
        return $this->order->getCustomerTaxvat();
    }

    /**
     * Returns order shipping total amount.
     *
     * @return float|null
     */
    public function getShippingAmount()
    {
        return $this->order->getShippingAmount();
    }

    /**
     * Returns order discount total amount.
     *
     * @return float|null
     */
    public function getDiscountAmount()
    {
        return $this->order->getDiscountAmount();
    }

    /**
     * Returns order tax total amount.
     *
     * @return float|null
     */
    public function getTaxAmount()
    {
        return $this->order->getTaxAmount();
    }

    /**
     * Returns order base moip interest amount.
     *
     * @return float|null
     */
    public function getBaseMoipInterestAmount()
    {
        return $this->order->getBaseMoipInterestAmount();
    }

    /**
     * Returns order quote id.
     *
     * @return float|null
     */
    public function getQuoteId()
    {
        return $this->order->getQuoteId();
    }

    /**
     * Set order moip interest amount.
     *
     * @param int $interest
     *
     * @return float|null
     */
    public function setMoipInterestAmount($interest)
    {
        return $this->order->setMoipInterestAmount($interest);
    }

    /**
     * Set order base moip interest amount.
     *
     * @param float $interest
     *
     * @return float|null
     */
    public function setBaseMoipInterestAmount($interest)
    {
        return $this->order->setBaseMoipInterestAmount($interest);
    }
}
