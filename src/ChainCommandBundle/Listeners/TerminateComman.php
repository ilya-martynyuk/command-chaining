<?php

namespace ChainCommandBundle\Listeners;

use ChainCommandBundle\Models\ChainedCommand;
use ChainCommandBundle\Services\ChainCommandService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Listens on console command terminate event, and checks whether terminated command has chained members.
 * In case of existence, executes them with predefined arguments.
 *
 * Also this class is responsible for logging of buffered output.
 *
 * @package ChainCommandBundle\Listeners
 */
class TerminateCommand implements ContainerAwareInterface
{
    /**
     * Instance of chained command service.
     *
     * @var ChainCommandService
     */
    protected $chainCommandService;

    /**
     * Dependency injection container.
     *
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Chain command logger.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * TerminateCommandListener constructor.
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
     * Checks whether command has chained members, and executes them in case of existence.
     *
     * @param ConsoleTerminateEvent $event
     */
    public function onConsoleTerminate(ConsoleTerminateEvent $event)
    {
        $command = $event->getCommand();
        $commandName = $command->getName();
        $output = $event->getOutput();

        // Checks whether command belongs to  chain commands (is root or member of chain).
        if (!$this->chainCommandService->isBelongsToChain($commandName)) {
            return;
        }

        if (get_class($output) !== 'Symfony\Component\Console\Output\BufferedOutput') {
            return;
        }

        $fetched = trim($output->fetch());

        $consoleOutput = new ConsoleOutput();
        $consoleOutput->writeln($fetched);

        $this
            ->logger
            ->addInfo($fetched);

        if ($this->chainCommandService->isRootCommand($commandName)) {
            $this
                ->logger
                ->addInfo(sprintf("Executing %s chain members:", $commandName));

            $chainedCommands = $this
                ->chainCommandService
                ->getChainedCommands($command->getName());

            foreach ($chainedCommands as $command) {
                $this->executeChainedCommand($command);
            }

            $this
                ->logger
                ->addInfo(sprintf("Execution of %s chain completed.", $commandName));
        }
    }

    /**
     * Launching of chained commands.
     *
     * @param ChainedCommand $chainedCommand Chained command to launch.
     */
    protected function executeChainedCommand(ChainedCommand $chainedCommand)
    {
        $kernel = $this->container->get('kernel');

        $application = new Application($kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput(
            array_merge(
                [
                    'command' => $chainedCommand->getName()
                ],
                $chainedCommand->getArguments()
            )
        );

        // Wee need to log command output both to the file and console.
        $bufferedOutput = new BufferedOutput();

        // Marking command as launched internally (we will be use this flag latter, in AccessChecker).
        $this
            ->chainCommandService
            ->markLaunchedCommand($chainedCommand->getName());

        $application->run($input, $bufferedOutput);

        // Unmarking launched internally command.
        $this
            ->chainCommandService
            ->unmarkLaunchedCommand();
    }
}