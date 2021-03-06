<?php

namespace ChainCommandBundle\Services;

use ChainCommandBundle\Exceptions\ChainCommandException;
use ChainCommandBundle\Models\ChainedCommand;
use Psr\Log\LoggerInterface;

/**
 * This class is responsible for dealing with chain of console commands.
 * It contains all registered chains, and has methods to dealing with them.
 *
 * @package ChainCommandBundle\Services
 */
class ChainCommandService
{
    /**
     * Logger instance.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Contains all registered chains.
     *
     * @var array
     */
    protected $chainsRegistry = [];

    /**
     * Contains command which is launched internally.
     * We will allowing executing this command from our AccessChecker.
     *
     * @var null
     */
    protected $launchedCommand = null;

    /**
     * ChainCommandService constructor.
     *
     * Initializing internal variables.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * This function checks whether $parentCommand does not has recursively call by $childCommand.
     *
     * It allows us to be able to find deep recursion chaining:
     * [
     *  a => b,
     *  b => c,
     *  c => a
     * ]
     *
     * Or simple recursion chaining:
     * [
     *  a => b,
     *  b => a
     * ]
     *
     * @param $parentCommand Root command name.
     * @param $childCommand Command name which should be chained to root command.
     * @throws ChainCommandException In case if those commands are recursively.
     */
    protected function checkForRecursivelyChaining($parentCommand, $childCommand)
    {
        $childChainedCommands = $this->getChainedCommands($childCommand);

        foreach($childChainedCommands as $command) {
            if ($command->getName() === $parentCommand) {
                throw new ChainCommandException(
                    "Command '$parentCommand' is already chained by '$childCommand'. Recursively chaining detected"
                );
            }

            // Lets check all child commands recursively.
            $this->checkForRecursivelyChaining($command->getName(), $childCommand);
        }
    }

    /**
     * Register new chain.
     *
     * @param $parentCommand Command name which is a root of new chain.
     * @param $childCommand Command name which is a member of root command.
     * @param array $arguments Member command arguments.
     * @return $this
     * @throws ChainCommandException In case of attempting to create recursively chain.
     */
    public function addChain($parentCommand, $childCommand, array $arguments = [])
    {
        if ($parentCommand === $childCommand) {
            throw new ChainCommandException(
                "Trying to create chain of '$childCommand' with himself. Self chaining is not allowed"
            );
        }

        if (!array_key_exists($parentCommand, $this->chainsRegistry)) {
            $this->chainsRegistry[$parentCommand] = [];
            $this
                ->logger
                ->addInfo("$parentCommand is a master command of a command chain that has registered member commands");
        }

        $chainedCommand = new ChainedCommand($childCommand);
        $chainedCommand->setArguments($arguments);

        // Skipp this chaining in case if the same chain is already exist.
        if (array_search($chainedCommand, $this->chainsRegistry[$parentCommand])) {
            return $this;
        }

        $this->checkForRecursivelyChaining($parentCommand, $childCommand);
        $this->chainsRegistry[$parentCommand][] = $chainedCommand;

        $this
            ->logger
            ->addInfo("$childCommand registered as a member of $parentCommand command chain");

        return $this;
    }

    /**
     * Checks whether command belongs to some chain (command is a member or root of chain).
     *
     * @param $commandName Command name to check.
     * @return bool
     */
    public function isBelongsToChain($commandName)
    {
        if ($this->isRootCommand($commandName)) {
            return true;
        }

        return !!$this->findParentCommand($commandName);
    }

    /**
     * Checks whether command a root of chain.
     *
     * @param $commandName Command name to check.
     * @return bool
     */
    public function isRootCommand($commandName)
    {
        return array_key_exists($commandName, $this->chainsRegistry);
    }

    /**
     * Returns an array of members of $parentCommand (if they exists).
     *
     * @param $parentCommand
     * @return array An array of chained commands.
     */
    public function getChainedCommands($parentCommand)
    {
        if (!array_key_exists($parentCommand, $this->chainsRegistry)) {
            return [];
        }

        return $this->chainsRegistry[$parentCommand];
    }

    /**
     * This function is used for getting of root command for certain command.
     * Usually used for determining if command is a member of chain.
     *
     * @param $childCommandName
     * @return bool|string False or root command name.
     */
    public function findParentCommand($childCommandName)
    {
        foreach ($this->chainsRegistry as $rootCommandName => $members) {
            foreach ($members as $childCommand) {
                if ($childCommand->getName() === $childCommandName) {
                    return $rootCommandName;
                }
            }
        }

        return false;
    }

    /**
     * Mark command as launched internally. This mean that command launched from our event listener, not manually.
     *
     * @param $commandName
     */
    public function markLaunchedCommand($commandName)
    {
        $this->launchedCommand = $commandName;
    }

    /**
     * Unmark command which was launched internally .
     */
    public function unmarkLaunchedCommand()
    {
        $this->launchedCommand = null;
    }

    /**
     * Checks whether command was launched from our event listener.
     *
     * @param $commandName
     * @return bool
     */
    public function isLaunchedInternally($commandName)
    {
        return $this->launchedCommand === $commandName;
    }
}