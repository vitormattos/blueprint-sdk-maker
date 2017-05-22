<?php
use PHPUnit\Framework\TestCase;
use BlueprintSdkMaker\Parser;

final class ParserTest extends TestCase
{
    public function testValidateFilename()
    {
        $parser = new Parser('bla.apib');
        $this->assertEquals($parser->getFile(), 'bla.apib');
    }
}