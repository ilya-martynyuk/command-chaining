<?php

namespace ChainCommandBundle\Models;

/**
 * Model for representing of registered chain member command.
 *
 * @package ChainCommandBundle\Models
 */
class ChainedCommand
{
    /**
     * Command name.
     *
     * @var string
     */
    protected $name;

    /**
     * Command arguments.
     * For example:
     * [
     *  argument1,
     *  argument2,
     *  --option1,
     *  --option2
     * ]
     *
     * @var array
     */
    protected $arguments;

    /**
     * ChainedCommand constructor.
     *
     * @param string $commandName Command name.
     */
    public function __construct($commandName)
    {
        $this->arguments = [];
        $this->name = $commandName;
    }

    /**
     * Returns command name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets command name.
     *
     * @param $name New command name.
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Returns command arguments.
     *
     * @return array An array of arguments
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * Sets command arguments.
     *
     * @param array $arguments An array of new command arguments.
     * @return $this
     */
    public function setArguments(array $arguments)
    {
        $this->arguments = $arguments;

        return $this;
    }
}