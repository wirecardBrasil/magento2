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
     * @var Filesystem
     */
    protected $_filesystem;

    /**
     * @var OutputInterface
     */
    protected $_output;

    /**
     * AbstractModel constructor.
     *
     * @param LoggerInterface $logger
     * @param Filesystem      $filesystem
     */
    public function __construct(
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * @param OutputInterface $output
     */
    public function setOutput(OutputInterface $output)
    {
        $this->_output = $output;
    }

    /**
     * @param $text
     */
    protected function write($text)
    {
        if ($this->_output instanceof OutputInterface) {
            $this->_output->write($text);
        }
    }

    /**
     * @param $text
     */
    protected function writeln($text)
    {
        if ($this->_output instanceof OutputInterface) {
            $this->_output->writeln($text);
        }
    }
}
