<?php
/**
 * Copyright © Wirecard Brasil. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Moip\Magento2\Api\Data;

/**
 * Interface MoipInterestInterface - Data Moip Interest.
 */
interface MoipInterestInterface
{
    /**
     * @var string
     */
    const MOIP_INTEREST_AMOUNT = 'moip_interest_amount';

    /**
     * @var string
     */
    const BASE_MOIP_INTEREST_AMOUNT = 'base_moip_interest_amount';

    /**
     * Get Installment for Moip Interest.
     *
     * @return float
     */
    public function getInstallmentForInterest();

    /**
     * Set Installment for Moip Interest.
     *
     * @param float $moipInterest
     *
     * @return void
     */
    public function setInstallmentForInterest($moipInterest);
}
