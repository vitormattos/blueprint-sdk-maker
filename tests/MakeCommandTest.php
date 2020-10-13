<?php

use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;
use BlueprintSdkMaker\Command\MakeCommand;
use Symfony\Component\Console\Tester\CommandTester;

final class MakeCommandTest extends TestCase
{
    protected $rootDir;

    public function setUp(): void
    {
        $this->rootDir = self::getUniqueTmpDirectory();
    }

    public function testMakeCommandInvalidApibFile()
    {
        $command = new MakeCommand();
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'apib-file' => 'invalid.apib',
            '--directory' => $this->rootDir,
            '--namespace' => 'BlueprintApi'
        ]);
        $output = $commandTester->getDisplay();
        $this->assertEquals("invalid apib file.\n", $output);
    }

    public function testInvalidDrafterBinary()
    {
        $command = new MakeCommand();
        $commandTester = new CommandTester($command);
        $apib_files = array_diff(scandir(__DIR__ . '/fixtures'), array('..', '.'));

        $commandTester->execute([
            'apib-file' => __DIR__ . DIRECTORY_SEPARATOR .
                'fixtures' . DIRECTORY_SEPARATOR .
                current($apib_files) . DIRECTORY_SEPARATOR .
                'ApiBlueprint.apib',
            '--directory' => $this->rootDir,
            '--namespace' => 'BlueprintApi',
            '--drafter-bin' => 'blablabin'
        ]);
        $output = $commandTester->getDisplay();

        $this->assertRegExp('/The drafter command is mandatory/', $output);
    }

    /**
     * @dataProvider resourceProvider
     */
    public function testMakeCommand(SplFileInfo $testDirectory)
    {
        $command = new MakeCommand();
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'apib-file' => $testDirectory->getRealPath() . DIRECTORY_SEPARATOR . 'ApiBlueprint.apib',
            '--directory' => $this->rootDir,
            '--namespace' => 'BlueprintApi',
            '--no-phar' => true
        ]);
        $output = $commandTester->getDisplay();
        $this->assertRegExp('/Generate .*.php/', $output);

        $expectedFinder = new Finder();
        $expectedFinder->in($testDirectory->getRealPath() . DIRECTORY_SEPARATOR . 'expected' . DIRECTORY_SEPARATOR . 'src/');

        $generatedFinder = new Finder();
        $generatedFinder->in($this->rootDir . DIRECTORY_SEPARATOR . 'src');

        $this->assertEquals(count($expectedFinder), count($generatedFinder), 'Failure in generate files');

        foreach ($generatedFinder as $generatedFile) {
            $generatedData[$generatedFile->getRelativePathname()] = $generatedFile->getPathName();
        }

        foreach ($expectedFinder as $expectedFile) {
            $this->assertArrayHasKey($expectedFile->getRelativePathname(), $generatedData);

            if ($expectedFile->isFile()) {
                $expectedPath = $expectedFile->getRealPath();
                $path = $expectedFile->getRelativePathname();
                $actualPath = $generatedData[$expectedFile->getRelativePathname()];
                //file_put_contents($expectedPath, file_get_contents($actualPath));
                $this->assertFileEquals(
                    $expectedPath,
                    $actualPath,
                    "Expected " . $expectedPath . " got " . $actualPath
                );
            }
        }
    }

    public function testMakePhar()
    {
        $apib_files = array_diff(scandir(__DIR__ . '/fixtures'), array('..', '.'));
        $command = new MakeCommand();
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'apib-file' => __DIR__ . DIRECTORY_SEPARATOR .
                'fixtures' . DIRECTORY_SEPARATOR .
                current($apib_files) . DIRECTORY_SEPARATOR .
                'ApiBlueprint.apib',
            '--directory' => $this->rootDir,
            '--namespace' => 'BlueprintApi',
        ]);
        $this->assertFileExists($this->rootDir . DIRECTORY_SEPARATOR . 'api.phar');

        ini_set('phar.readonly', 1);

        $commandTester->execute([
            'apib-file' => __DIR__ . DIRECTORY_SEPARATOR .
                'fixtures' . DIRECTORY_SEPARATOR .
                current($apib_files) . DIRECTORY_SEPARATOR .
                'ApiBlueprint.apib',
            '--directory' => $this->rootDir,
            '--namespace' => 'BlueprintApi',
        ]);
        $output = $commandTester->getDisplay();
        $this->assertRegExp('/Enable phar.readonly into php.ini setting phar.readonly to 0/', $output);
    }

    public function resourceProvider()
    {
        $finder = new Finder();
        $finder->directories()->in(__DIR__ . '/fixtures');
        $finder->depth('< 1');

        $data = array();

        foreach ($finder as $directory) {
            $data[] = [$directory];
        }

        return $data;
    }


    public static function getUniqueTmpDirectory()
    {
        $attempts = 5;
        $root = sys_get_temp_dir();

        do {
            $unique = $root . DIRECTORY_SEPARATOR . uniqid('blueprint-sdk-maker-test-' . rand(1000, 9000));

            if (!file_exists($unique) && mkdir($unique, 0777)) {
                return realpath($unique);
            }
        } while (--$attempts);

        throw new \RuntimeException('Failed to create a unique temporary directory.');
    }

    private function rrmdir($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . "/" . $object))
                        $this->rrmdir($dir . "/" . $object);
                    else
                        unlink($dir . "/" . $object);
                }
            }
            rmdir($dir);
        }
    }
}
