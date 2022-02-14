<?php
/**
 * Copyright © Moip by PagSeguro. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Moip\Magento2\Model\Data;

use Magento\Framework\Api\AbstractSimpleObject;
use Moip\Magento2\Api\Data\MoipInterestInterface;

/**
 * Class MoipInterest - Model data.
 */
class MoipInterest extends AbstractSimpleObject implements MoipInterestInterface
{
    /**
     * @inheritdoc
     */
    public function getInstallmentForInterest()
    {
        return $this->_get(MoipInterestInterface::MOIP_INTEREST_AMOUNT);
    }

    /**
     * @inheritdoc
     */
    public function setInstallmentForInterest($moipInterest)
    {
        return $this->setData(MoipInterestInterface::MOIP_INTEREST_AMOUNT, $moipInterest);
    }
}
