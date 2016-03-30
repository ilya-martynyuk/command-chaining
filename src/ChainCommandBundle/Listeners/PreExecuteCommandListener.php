<?php

namespace ChainCommandBundle\Listeners;

use ChainCommandBundle\Services\ChainCommandService;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Class PreExecuteCommandListener
 *
 * Used for terminating of commands which are members of some chain.
 *
 * @package ChainCommandBundle\Listeners
 */
class PreExecuteCommandListener
{
    /**
     * Chain command service.
     *
     * @var ChainCommandService
     */
    protected $chainCommandService;

    /**
     * Injecting service dependencies.
     *
     * @param ChainCommandService $chainCommandService
     */
    public function __construct(ChainCommandService $chainCommandService)
    {
        $this->chainCommandService = $chainCommandService;
    }

    /**
     * @param ConsoleCommandEvent $event
     */
    public function onConsoleCommand(ConsoleCommandEvent $event) {
        $output = $event->getOutput();
        $command = $event->getCommand();

        $parentCommandName = $this
            ->chainCommandService
            ->findParentCommand($command->getName())
        ;

        // Seems this command is not a member of any chain. Allow executing it.
        if (!$parentCommandName) {
            return;
        }

        $output->writeln(
            sprintf(
                "<error>Error: '%s' command is a member of '%s' command chain and cannot be executed on its own.</error>",
                $command->getName(), $parentCommandName
            )
        );

        $event->disableCommand();
    }
}