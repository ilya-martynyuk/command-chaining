<?php

namespace BarBundle\Command;

use ChainCommandBundle\Services\ChainCommandService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This command is only for demo. It outputs "Hi from Bar!" message.
 * Also it registers 'bar:hi' command as member of self chain.
 *
 * @package BarBundle\Command
 */
class BarHiCommand extends ContainerAwareCommand
{
    /**
     * Chain command service.
     *
     * @var ChainCommandService
     */
    protected $chainCommandService;

    /**
     * BarHiCommand constructor.
     *
     * @param ChainCommandService $chainCommandService
     */
    public function __construct(ChainCommandService $chainCommandService)
    {
        $this->chainCommandService = $chainCommandService;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('bar:hi')
            ->setDescription('This command is used for outputting "hi" message from "BarBundle"');

        // Lets register self as a root of chain, and add 'bar:hi' command as a member of this chain.
        $this
            ->chainCommandService
            ->addChain('foo:hello', 'bar:hi');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Hi from Bar!');
    }
}
