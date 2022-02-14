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
use Moip\Magento2\Model\Console\Command\Preference\Create;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateWebhook extends Command
{
    /**
     * @const string
     */
    public const WEBHOOK_LINK = 'link';

    /**
     * @var Create
     */
    protected $create;

    /**
     * @var State
     */
    protected $state;

    /**
     * CreateWebhook constructor.
     *
     * @param State  $state
     * @param Create $create
     */
    public function __construct(
        State $state,
        Create $create
    ) {
        $this->state = $state;
        $this->create = $create;
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
        $this->create->setOutput($output);

        $link = $input->getArgument(self::WEBHOOK_LINK);
        $this->create->preference($link);
    }

    /**
     * Configure.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setName('moip:webhooks:set_preference');
        $this->setDescription('Manually set the preferred url for Webhooks');
        $this->setDefinition(
            [new InputArgument(self::WEBHOOK_LINK, InputArgument::REQUIRED, 'Domain')]
        );
        parent::configure();
    }
}
