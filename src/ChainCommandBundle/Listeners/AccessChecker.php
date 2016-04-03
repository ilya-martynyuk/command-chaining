<?php

namespace ChainCommandBundle\Listeners;

use ChainCommandBundle\Services\ChainCommandService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Event\ConsoleCommandEvent;

/**
 * Used for terminating of commands which launched manually from console and which are members of chain.
 *
 * @package ChainCommandBundle\Listeners
 */
class AccessChecker
{
    /**
     * Chain command service.
     *
     * @var ChainCommandService
     */
    protected $chainCommandService;

    /**
     * Chain command logger.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Injecting service dependencies.
     *
     * @param ChainCommandService $chainCommandService Chain command service
     * @param LoggerInterface $logger Chain command logger
     */
    public function __construct(ChainCommandService $chainCommandService, LoggerInterface $logger)
    {
        $this->chainCommandService = $chainCommandService;
        $this->logger = $logger;
    }

    /**
     * @param ConsoleCommandEvent $event
     */
    public function onConsoleCommand(ConsoleCommandEvent $event)
    {
        $output = $event->getOutput();
        $command = $event->getCommand();

        // Lets check whether command belongs to chain command service, and should be logged.
        if (!$this->chainCommandService->isBelongsToChain($command->getName())) {
            return;
        }

        // Is it a member of any chain?
        $parentCommandName = $this
            ->chainCommandService
            ->findParentCommand($command->getName());

        // Seems this command is not a member of any chain. Allow executing it.
        if (!$parentCommandName) {
            return;
        }

        // Ok, this command is a member, but maybe it was launched internally from our listener?
        $isLaunchedInternally = $this
            ->chainCommandService
            ->isLaunchedInternally($command->getName());

        // Command launched from our listener. Allow executing.
        if ($isLaunchedInternally) {
            return;
        }

        // Otherwise means that user attempts to launch command which is a member of chain, so we nned to reject it.
        $output->writeln(
            sprintf(
                "<error>Error: '%s' command is a member of '%s' command chain and cannot be executed on its own.</error>",
                $command->getName(), $parentCommandName
            )
        );

        $event->disableCommand();
    }
}