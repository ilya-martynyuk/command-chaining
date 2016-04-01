<?php

namespace ChainCommandBundle\Tests\Functional;

use ChainCommandBundle\Services\ChainCommandService;
use ChainCommandBundle\Tests\Functional\Stubs\MemberCommand;
use ChainCommandBundle\Tests\Functional\Stubs\RootCommand;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Bundle\FrameworkBundle\Client;

class ChainCommandBundleTest extends WebTestCase
{
    protected $consoleApplication;

    protected $chainCommandService;

    protected $application;

    protected $client;

    /**
     * Runs a command and returns it output
     */
    public function runCommand(Client $client, $command)
    {
        $fp = tmpfile();
        $input = new StringInput($command);
        $output = new StreamOutput($fp);

        $this->application->run($input, $output);

        fseek($fp, 0);
        $output = '';
        while (!feof($fp)) {
            $output = fread($fp, 4096);
        }
        fclose($fp);

        return $output;
    }

    public function setUp()
    {
        $loggerMock = $this
            ->getMockBuilder('Monolog\Logger')
            ->disableOriginalConstructor()
            ->getMock();

        $this->chainCommandService = new ChainCommandService($loggerMock);

        $this->client = self::createClient();
        $this->application = new Application($this->client->getKernel());
        $this->application->setAutoExit(false);
    }

    public function testDsw()
    {
        $this->application->add(new RootCommand());

        $output = $this
            ->runCommand($this->client, "command:root");
    }
}