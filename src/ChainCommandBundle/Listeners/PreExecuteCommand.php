<?php

namespace ChainCommandBundle\Listeners;

use ChainCommandBundle\Services\ChainCommandService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Used for dealing with chain commands.
 * Checks whether
 *
 * @package ChainCommandBundle\Listeners
 */
class PreExecuteCommand implements ContainerAwareInterface
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
        $commandName = $command->getName();

        // We don't need to process of any commands which are not related to chain (not a member and not a root).
        if (!$this->chainCommandService->isBelongsToChain($commandName)) {
            return;
        }

        $parentCommandName = $this
            ->chainCommandService
            ->findParentCommand($commandName);

        // Command is a member of chain. Lets checks whether command can be executed.
        if ($parentCommandName) {
            // Maybe command launched internally from our listener?
            $isLaunchedInternally = $this
                ->chainCommandService
                ->isLaunchedInternally($commandName);

            // Otherwise, we don't allow manually execution of commands which are members of chain.
            if (!$isLaunchedInternally) {
                $output->writeln(
                    sprintf(
                        "<error>Error: '%s' command is a member of '%s' command chain and cannot be executed on its own.</error>",
                        $commandName, $parentCommandName
                    )
                );

                $event->disableCommand();
                return;
            }
        }

        // We need to log all of chain output. So lets change output of command to buffered and re execute the command.
        if (!$this->isOutputBuffered($output)) {
            $this->reExecuteWithBuffering($event);
            $event->disableCommand();
            return;
        }

        // For root commands we need to logging message about their starting of execution.
        if ($this->chainCommandService->isRootCommand($commandName)) {
            $this
                ->logger
                ->addInfo(
                    sprintf("Executing %s command itself first:", $command->getName())
                );
        }
    }

    /**
     * Checks whether given output is BufferedOutput.
     *
     * @param OutputInterface $output Instance of output to check.
     * @return bool
     */
    protected function isOutputBuffered(OutputInterface $output)
    {
        return get_class($output) === 'Symfony\Component\Console\Output\BufferedOutput';
    }

    protected function buildApplication($kernel)
    {
        return new Application($kernel);
    }

    /**
     * Executes command again with BufferedOutput injects.
     * Preserve all of command arguments and options.
     *
     * @param ConsoleCommandEvent $event
     * @throws \Exception
     */
    protected function reExecuteWithBuffering(ConsoleCommandEvent $event)
    {
        $kernel = $this->container->get('kernel');
        $application = $this->buildApplication($kernel);
        $bufferedOutput = new BufferedOutput();
        $preparedOptions = $this
            ->prepareCommandOptions(
                $event
                    ->getInput()
                    ->getOptions()
            );
        $application->setAutoExit(false);

        $input = new ArrayInput(
            array_merge(
                $event->getInput()->getArguments(),
                $preparedOptions
            )
        );

        $application->run($input, $bufferedOutput);
    }

    /**
     * Appends '--' to all of array keys.
     *
     *
     * @param array $options An array of options without '--' appended.
     * @return array Prepared options with '--' appended.
     */
    protected function prepareCommandOptions(array $options)
    {
        // Leaves only enabled options.
        $options = array_filter($options, function($optionValue){
            return $optionValue !== false;
        });

        $preparedOptions = [];

        // Appends '--' to all of keys.
        foreach ($options as $k => $option) {
            $preparedOptions["--$k"] = $option;
        }

        return $preparedOptions;
    }
}