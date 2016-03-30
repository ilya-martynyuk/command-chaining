<?php

namespace BarBundle\Command;

use ChainCommandBundle\Services\ChainCommandService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BarHiCommand extends ContainerAwareCommand
{
    protected $chainCommandService;

    public function __construct(ChainCommandService $chainCommandService)
    {
        $this->chainCommandService = $chainCommandService;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('bar:hi')
            ->setDescription('This command is used for outputting "hi" message from "BarBundle"')
        ;

        $this
            ->chainCommandService
            ->addChain('foo:hello', $this->getName())
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Hi from Bar!');
    }
}
