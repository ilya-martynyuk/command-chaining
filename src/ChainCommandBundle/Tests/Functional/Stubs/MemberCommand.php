<?php

namespace ChainCommandBundle\Tests\Functional\Stubs;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MemberCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('command:member')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('I am a member of chain command!');
    }
}
