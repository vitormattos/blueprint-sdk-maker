<?php
use PHPUnit\Framework\TestCase;
use BlueprintSdkMaker\Parser;
use org\bovigo\vfs\vfsStream;
use Symfony\Component\Finder\Finder;
use BlueprintSdkMaker\Command\MakeCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Tester\CommandTester;

final class ParserTest extends TestCase
{
    /**
     * @var  vfsStreamDirectory
     */
    private $root;

    public function setUp()
    {
        $this->root = vfsStream::setup();
    }
    
    public function testValidateApibString()
    {
        $parser = new Parser('bla.apib', vfsStream::url('root'));
        $this->assertEquals($parser->getApib(), 'bla.apib');
    }
    
    /**
     * @dataProvider resourceProvider
     */
    public function testRessources(SplFileInfo $testDirectory)
    {
        $command = new MakeCommand();
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'apib-file' => $testDirectory->getRealPath().DIRECTORY_SEPARATOR.'ApiBlueprint.apib',
            '--directory' => vfsStream::url('root'),
            '--namespace' => 'BlueprintApi'
        ]);
        $output = $commandTester->getDisplay();
        $this->assertEquals("Generate vfs://root/src/Request.php\n", $output);

        $expectedFinder = new Finder();
        $expectedFinder->in($testDirectory->getRealPath() . DIRECTORY_SEPARATOR . 'expected'.DIRECTORY_SEPARATOR.'src/');

        $generatedFinder = new Finder();
        $generatedFinder->in(vfsStream::url('root/src'));

        $this->assertEquals(count($expectedFinder), count($generatedFinder), 'Failute in generate files');
        
        foreach ($generatedFinder as $generatedFile) {
            $generatedData[$generatedFile->getRelativePathname()] = $generatedFile->getPathName();
        }
        
        foreach ($expectedFinder as $expectedFile) {
            $this->assertArrayHasKey($expectedFile->getRelativePathname(), $generatedData);
            
            if ($expectedFile->isFile()) {
                $expectedPath = $expectedFile->getRealPath();
                $path = $expectedFile->getRelativePathname();
                $actualPath   = $generatedData[ $expectedFile->getRelativePathname() ];
                
                $this->assertEquals(
                    file_get_contents($expectedPath),
                    file_get_contents($actualPath),
                    "Expected " . $expectedPath . " got " . $actualPath
                    );
            }
        }
    }
    
    public function resourceProvider()
    {
        $finder = new Finder();
        $finder->directories()->in(__DIR__.'/fixtures');
        $finder->depth('< 1');
        
        $data = array();
        
        foreach ($finder as $directory) {
            $data[] = [$directory];
        }
        
        return $data;
    }
}