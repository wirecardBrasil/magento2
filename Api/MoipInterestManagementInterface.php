<?php
/**
 * Copyright © Wirecard Brasil. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Moip\Magento2\Api;

/**
 * Interface for saving the checkout moip interest to the quote for orders.
 *
 * @api
 */
interface MoipInterestManagementInterface
{
    /**
     * Set in the moip interest amount per installment number.
     *
     * @param int                                           $cartId
     * @param \Moip\Magento2\Api\Data\MoipInterestInterface $installment
     *
     * @return string
     */
    public function saveMoipInterest(
        $cartId,
        \Moip\Magento2\Api\Data\MoipInterestInterface $installment
    );
}
