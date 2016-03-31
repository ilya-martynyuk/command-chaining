<?php

namespace ChainCommandBundle\Tests\Functional\Stubs;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RootCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('command:root')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('I am root command!');
    }
}
