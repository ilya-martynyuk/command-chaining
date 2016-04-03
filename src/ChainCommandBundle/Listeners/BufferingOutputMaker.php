<?php

namespace ChainCommandBundle\Listeners;

use ChainCommandBundle\Services\ChainCommandService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * This class is used for recreating commands and injecting of buffered output into them.
 * It filters only commands which are belongs to chain.
 *
 * @package ChainCommandBundle\Listeners
 */
class BufferingOutputMaker implements ContainerAwareInterface
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
     * Dependency injection container.
     *
     * @var ContainerInterface
     */
    protected $container;

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
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * Recreates chain commands with injection of buffered output.
     *
     * @param ConsoleCommandEvent $event
     * @throws \Exception
     */
    public function onConsoleCommand(ConsoleCommandEvent $event)
    {
        $output = $event->getOutput();
        $command = $event->getCommand();

        // Lets check whether command belongs to chain command service, and should be logged.
        if (!$this->chainCommandService->isBelongsToChain($command->getName())) {
            return;
        }

        // Command is already has buffered output. Skip this event.
        if (get_class($output) === 'Symfony\Component\Console\Output\BufferedOutput') {
            return;
        }

        // We need to log output of commands which related to chain command service.
        // So lets restart command with BufferedOutput
        $kernel = $this->container->get('kernel');

        $application = new Application($kernel);
        $application->setAutoExit(false);

        $bufferedOutput = new BufferedOutput();
        $options = $event->getInput()->getOptions();

        $options = array_filter($options, function($optionValue){
            return $optionValue !== false;
        });

        $preparedOptions = [];

        foreach ($options as $k => $option) {
            $preparedOptions["--$k"] = $option;
        }

        $input = new ArrayInput(
            array_merge(
                $event->getInput()->getArguments(),
                $preparedOptions
            )
        );

        $application->run($input, $bufferedOutput);

        $event->disableCommand();
    }
}