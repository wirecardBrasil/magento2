<?php

namespace Moip\Magento2\Model\Console\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AbstractModel
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var OutputInterface
     */
    protected $_output;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * Output.
     *
     * @param OutputInterface $output
     *
     * @return voind
     */
    public function setOutput(OutputInterface $output)
    {
        $this->_output = $output;
    }

    /**
     * Console Write.
     *
     * @param string $text
     *
     * @return void
     */
    protected function write(string $text)
    {
        if ($this->_output instanceof OutputInterface) {
            $this->_output->write($text);
        }
    }

    /**
     * Console WriteLn.
     *
     * @param string $text
     *
     * @return void
     */
    protected function writeln($text)
    {
        if ($this->_output instanceof OutputInterface) {
            $this->_output->writeln($text);
        }
    }
}
