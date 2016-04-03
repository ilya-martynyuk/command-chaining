<?php

namespace ChainCommandBundle\Listeners;

use ChainCommandBundle\Services\ChainCommandService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Event\ConsoleCommandEvent;

/**
 * Any custom logic, related to chain services, can be written here.
 * For example logging starting of root command.
 *
 * @package ChainCommandBundle\Listeners
 */
class PreExecuteCommand
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
     * PreExecuteCommand constructor.
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
     * Custom operations on command execution.
     *
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

        if (get_class($output) !== 'Symfony\Component\Console\Output\BufferedOutput') {
            return;
        }

        if ($this->chainCommandService->isRootCommand($command->getName())) {
            $this
                ->logger
                ->addInfo(
                    sprintf("Executing %s command itself first:", $command->getName())
                );
        }
    }
}