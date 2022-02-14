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
use Moip\Magento2\Model\Console\Command\Preference\All;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListWebhook extends Command
{
    /**
     * @var All
     */
    protected $all;

    /**
     * @var State
     */
    protected $state;

    /**
     * @param State $state
     * @param All   $all
     */
    public function __construct(
        State $state,
        All $all
    ) {
        $this->state = $state;
        $this->all = $all;
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
        $this->all->setOutput($output);
        $this->all->all();
    }

    /**
     * Configure.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setName('moip:webhooks:list_preference');
        $this->setDescription('List of preferred urls for Webhooks');
        parent::configure();
    }
}
