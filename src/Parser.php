<?php
namespace VitorMattos\BlueprintSdkMaker;

class Parser
{
    /**
     * Documentation file
     *
     * @var string
     */
    private $file;
    
    /**
     * Create a new parser instance.
     *
     * @param string $filename
     */
    public function __construct($filename)
    {
        $this->file = $filename;
    }
    
    public function getFile()
    {
        return $this->file;
    }
}
