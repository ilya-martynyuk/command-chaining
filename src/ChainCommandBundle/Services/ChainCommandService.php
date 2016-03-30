<?php

namespace ChainCommandBundle\Services;

use ChainCommandBundle\Exceptions\ChainCommandException;
use Psr\Log\LoggerInterface;

class ChainCommandService
{
    /**
     * Logger instance
     *
     * @var LoggerInterface
     */
    protected $logger;

    protected $chainsRegistry = [];

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
     * @param $parentCommand Root command
     * @param $childCommand Command which should be chained to root command
     *
     * @throws ChainCommandException In case if those commands are recursively
     */
    protected function checkForRecursivelyChaining($parentCommand, $childCommand)
    {
        $childChainedCommands = $this->getChainedCommands($childCommand);

        foreach($childChainedCommands as $command) {
            if ($command['name'] === $parentCommand) {
                throw new ChainCommandException(
                    "Command '$parentCommand' is already chained by '$childCommand'. Recursively chaining detected"
                );
            }

            // Lets check all child commands recursively.
            $this->checkForRecursivelyChaining($command['name'], $childCommand);
        }
    }

    public function addChain($parentCommand, $childCommand, array $arguments = [])
    {
        if ($parentCommand === $childCommand) {
            throw new ChainCommandException(
                "Trying to create chain of '$childCommand' with himself. Self chaining is not allowed"
            );
        }

        if (!array_key_exists($parentCommand, $this->chainsRegistry)) {
            $this->chainsRegistry[$parentCommand] = [];
        }

        $chainSettings = [
            'name' => $childCommand,
            'arguments' => $arguments
        ];

        // Skipp this chaining in case if the same chain is already exist.
        if (array_search($chainSettings, $this->chainsRegistry[$parentCommand])) {
            return $this;
        }

        $this->checkForRecursivelyChaining($parentCommand, $childCommand);
        $this->chainsRegistry[$parentCommand][] = $chainSettings;

        return $this;
    }

    public function getChainedCommands($parentCommand)
    {
        if (!array_key_exists($parentCommand, $this->chainsRegistry)) {
            return [];
        }

        return $this->chainsRegistry[$parentCommand];
    }

    public function findParentCommand($childCommandName)
    {
        foreach ($this->chainsRegistry as $rootCommandName => $members) {
            foreach ($members as $childCommand) {
                if ($childCommand['name'] === $childCommandName) {
                    return $rootCommandName;
                }
            }
        }

        return false;
    }
}