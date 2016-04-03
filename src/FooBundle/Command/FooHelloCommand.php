<?php

namespace FooBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This command is only for demo. It outputs "Hello from Foo!" message.
 *
 * @package FooBundle\Command
 */
class FooHelloCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('foo:hello')
            ->setDescription('This command is used for outputting "hello" message from "FooBundle"');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Hello from Foo!');
    }
}
