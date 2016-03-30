<?php

namespace ChainCommandBundle\Tests\Unit\Services;

use ChainCommandBundle\Services\ChainCommandService;
use Monolog\Logger;

/**
 * Class ChainCommandServiceTest
 *
 * Contains logic for testing of ChainCommandService.
 *
 * @package ChainCommandBundle\Tests\Unit\Services
 */
class ChainCommandServiceTest extends \PHPUnit_Framework_TestCase
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
        $loggerMock = $this
            ->getMockBuilder('Monolog\Logger')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->object = new ChainCommandService($loggerMock);
    }

    /**
     * Simple adding new chain
     */
    public function testAddChain()
    {
        // Attaching 'foo1' command to 'bar' command.
        $this
            ->object
            ->addChain('bar', 'foo1')
        ;

        // Attaching 'foo2' command to 'bar' command.
        $this
            ->object
            ->addChain('bar', 'foo2', [
                '--option' => 'option'
            ])
        ;

        // Creating chain which is already exist (should be ignored).
        $this
            ->object
            ->addChain('bar', 'foo2', [
                '--option' => 'option'
            ])
        ;

        // But this chain is not the same as described above.
        // This chain has different options and should not be ignored.
        // We allow adding same commands with different options.
        $this
            ->object
            ->addChain('bar', 'foo2', [
                '--option' => 'option',
                '--option2' => 'option2'
            ])
        ;

        // Receiving all attached to 'bar' commands.
        $chainedCommands = $this
            ->object
            ->getChainedCommands('bar')
        ;

        $this->assertEquals([
            [
                'name' => 'foo1',
                'arguments' => []
            ], [
                'name' => 'foo2',
                'arguments' => [
                    '--option' => 'option'
                ]
            ], [
                'name' => 'foo2',
                'arguments' => [
                    '--option' => 'option',
                    '--option2' => 'option2'
                ]
            ]
        ], $chainedCommands);
    }

    public function testGetChainedCommands()
    {
        // Trying to get commands from command which doesn't has chained commands.
        $chainedCommands = $this
            ->object
            ->getChainedCommands('command_without_chains')
        ;

        $this->assertEquals([], $chainedCommands);
    }

    /**
     * Trying to create recursively chains.
     *
     * @expectedException \ChainCommandBundle\Exceptions\ChainCommandException
     * @expectedExceptionMessage Command 'foo' is already chained by 'bar'. Recursively chaining detected
     */
    public function testAddChainRecursively()
    {
        $this
            ->object
            ->addChain('bar', 'foo')
        ;

        $this
            ->object
            ->addChain('foo', 'bar')
        ;
    }

    /**
     * Trying to create self chain.
     *
     * @expectedException \ChainCommandBundle\Exceptions\ChainCommandException
     * @expectedExceptionMessage Trying to create chain of 'bar' with himself. Self chaining is not allowed
     */
    public function testAddChainToSelf()
    {
        $this
            ->object
            ->addChain('bar', 'bar')
        ;
    }

    /**
     * Trying to create recursively chains. Checking deep level of recursive.
     *
     * @expectedException \ChainCommandBundle\Exceptions\ChainCommandException
     * @expectedExceptionMessage Command 'foo' is already chained by 'bar'. Recursively chaining detected
     */
    public function testAddChainRecursivelyDeep()
    {
        $this
            ->object
            ->addChain('bar', 'foo')
        ;

        $this
            ->object
            ->addChain('foo', 'tar')
        ;

        $this
            ->object
            ->addChain('tar', 'bar')
        ;
    }

    public function testFindParentCommand()
    {
        $this
            ->object
            ->addChain('bar', 'foo')
        ;

        $this
            ->object
            ->addChain('bar', 'baz')
        ;

        $this->assertEquals('bar', $this->object->findParentCommand('foo'));
        $this->assertEquals('bar', $this->object->findParentCommand('baz'));
        $this->assertEquals(false, $this->object->findParentCommand('paz'));
        $this->assertEquals(false, $this->object->findParentCommand('bar'));
    }
}