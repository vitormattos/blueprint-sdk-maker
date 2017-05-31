<?php
use PHPUnit\Framework\TestCase;
use BlueprintSdkMaker\Parser;
use PhpParser\PrettyPrinter\Standard;

final class ParserTest extends TestCase
{
    public function testValidateApibString()
    {
        $parser = new Parser([
            'apib' => ['bla.apib']
        ]);
        $this->assertEquals($parser->getApib(), ['bla.apib']);
    }
    public function testOutputRequestMethodReturningJsonArray()
    {
        $prettyPrinter =  new Standard();
        
        $parser = new Parser([
            'apib' => ['bla.apib']
        ]);
        $parser->generate();
        $request = $parser->setClass('Request');
        $output = $prettyPrinter->prettyPrintFile([$request->getNode()]);
        $this->assertRegExp('/return json_decode\(\$res->getBody\(\)->getContents\(\), true\);/', $output);
    }
    public function testOutputRequestMethodReturningJsonObject()
    {
        $prettyPrinter =  new Standard();
        
        $parser = new Parser([
            'apib' => ['bla.apib'],
            'format' => 'json-object'
        ]);
        $parser->generate();
        $request = $parser->setClass('Request');
        $output = $prettyPrinter->prettyPrintFile([$request->getNode()]);
        $this->assertRegExp('/return json_decode\(\$res->getBody\(\)->getContents\(\)\);/', $output);
    }
    public function testOutputRequestMethodReturningRaw()
    {
        $prettyPrinter =  new Standard();
        
        $parser = new Parser([
            'apib' => ['bla.apib'],
            'format' => 'raw'
        ]);
        $parser->generate();
        $request = $parser->setClass('Request');
        $output = $prettyPrinter->prettyPrintFile([$request->getNode()]);
        $this->assertRegExp('/return \$res->getBody\(\)->getContents\(\);/', $output);
    }
    public function testSetClass()
    {
        $parser = new Parser([
            'apib' => ['bla.apib']
        ]);
        $class = $parser->setClass('bla');
        $this->assertInstanceOf('PhpParser\Builder\Class_', $class);
    }
}