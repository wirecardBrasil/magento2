<?php
/**
 * Copyright © Moip by PagSeguro. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Moip\Magento2\Console\Command\Preference;

use Magento\Framework\App\State;
use Moip\Magento2\Model\Console\Command\Preference\Delete;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeleteWebhook extends Command
{
    /**
     * @const string
     */
    public const WEBHOOK_ID = 'id';

    /**
     * @var Delete
     */
    protected $delete;

    /**
     * @var State
     */
    protected $state;

    /**
     * @param State  $state
     * @param Delete $delete
     */
    public function __construct(
        State $state,
        Delete $delete
    ) {
        $this->state = $state;
        $this->delete = $delete;
        parent::__construct();
    }

    /**
     * Execute.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
        $this->delete->setOutput($output);

        $ids[] = $input->getArgument(self::WEBHOOK_ID);
        $this->delete->delete($ids);
    }

    /**
     * Configure.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setName('moip:webhooks:delete_preference');
        $this->setDescription('Manually delete the preferred url for Webhooks');
        $this->setDefinition(
            [new InputArgument(self::WEBHOOK_ID, InputArgument::REQUIRED, 'ID Webhook - NPR-xxxx')]
        );
        parent::configure();
    }
}
