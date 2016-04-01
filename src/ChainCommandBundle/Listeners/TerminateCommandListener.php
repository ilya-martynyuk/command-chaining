<?php

namespace ChainCommandBundle\Listeners;

use ChainCommandBundle\Services\ChainCommandService;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TerminateCommandListener implements ContainerAwareInterface
{
    protected $chainCommandService;

    protected $container;

    /**
     * Injecting service dependencies.
     *
     * @param ChainCommandService $chainCommandService
     */
    public function __construct(ChainCommandService $chainCommandService)
    {
        $this->chainCommandService = $chainCommandService;
    }

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function onConsoleTerminate(ConsoleTerminateEvent $event)
    {
        $output = $event->getOutput();
        $command = $event->getCommand();

        $chainedCommands = $this
            ->chainCommandService
            ->getChainedCommands($command->getName());

        if (count($chainedCommands) === 0) {
            return;
        }

        foreach ($chainedCommands as $command) {
            $this->executeCommand($command);
        }
    }

    protected function executeCommand($command)
    {
        $kernel = $this->container->get('kernel');

        $application = new Application($kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput(
            array_merge(
                ['command' => $command['name']],
                $command['arguments']
            )
        );

        // You can use NullOutput() if you don't need the output
        $output = new ConsoleOutput();

        $this
            ->chainCommandService
            ->markLaunchedCommand($command['name']);

        $application->run($input, $output);

        $this
            ->chainCommandService
            ->unmarkLaunchedCommand($command['name']);
    }
}