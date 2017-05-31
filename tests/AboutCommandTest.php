<?php
use PHPUnit\Framework\TestCase;
use BlueprintSdkMaker\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

final class AboutCommandTest extends TestCase
{
    public function testAboutCommand()
    {
        $application = new Application();
        $command = $application->get('about');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
        $expected = "Blueprint SDK Maker - Create SDK client from API blueprint apib file
API Blueprint is a powerful high-level API description language for web APIs.
With this command you will parse doc from API Blueprint and generate a PHP SKD.
See https://github.com/vitormattos/blueprint-sdk-maker/ for more information.\n";
        $this->assertEquals($expected, $commandTester->getDisplay());
    }
}