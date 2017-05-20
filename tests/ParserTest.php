<?php
use PHPUnit\Framework\TestCase;
use VitorMattos\BlueprintParser\Parser;

final class ParserTest extends TestCase
{
    public function testValidateFilename()
    {
        $parser = new Parser('bla.apib');
        $this->assertEquals($parser->getFile(), 'bla.apib');
    }
}