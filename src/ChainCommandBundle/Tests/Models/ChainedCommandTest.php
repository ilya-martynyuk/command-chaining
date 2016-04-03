<?php

namespace ChainCommandBundle\Tests\Unit\Services;

use ChainCommandBundle\Models\ChainedCommand;
use ChainCommandBundle\Services\ChainCommandService;
use Monolog\Logger;

/**
 * Contains logic for testing of ChainCommandService.
 *
 * @package ChainCommandBundle\Tests\Unit\Services
 */
class ChainedCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Our test object
     *
     * @var ChainCommandService
     */
    protected $object;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->object = new ChainedCommand('Test command');
    }

    public function testCommonFunctional()
    {
        $this->assertEquals('Test command', $this->object->getName());

        $this->object->setArguments([
            'argument1',
            'argument2',
            '--option1' => 'option_value1',
            '--option2' => 'option_value2'
        ]);

        $this->assertEquals([
            'argument1',
            'argument2',
            '--option1' => 'option_value1',
            '--option2' => 'option_value2'
        ], $this->object->getArguments());
    }
}