<?php
use PHPUnit\Framework\TestCase;
use BlueprintSdkMaker\Console\Application;
use Symfony\Component\Console\Tester\ApplicationTester;

final class HelpCommandTest extends TestCase
{
    public function testHelpCommand()
    {
        $application = new Application();
        $application->setAutoExit(false);
        $ApplicationTester = new ApplicationTester($application);
        $ApplicationTester->run([]);
        $this->assertRegExp('/Blueprint API Maker/', $ApplicationTester->getDisplay());
    }
}