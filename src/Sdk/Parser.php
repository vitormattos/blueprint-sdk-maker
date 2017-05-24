<?php
namespace BlueprintSdkMaker;

use PhpParser\PrettyPrinter\Standard;
use PhpParser\BuilderFactory;
use PhpParser\Node;

class Parser
{
    /**
     * Content of apib file
     *
     * @var array
     */
    private $apib;
    private $namespace = 'Blueprint';
    private $rootClassName = 'Api';
    /**
     * Pretty printer
     *
     * @var \PhpParser\PrettyPrinter\Standard
     */
    private $Standard;
    /**
     * @see \PhpParser\BuilderFactory
     *
     * @var \PhpParser\BuilderFactory
     */
    private $BuilderFactory;
    /**
     *
     * @var \PhpParser\Builder\Class_[]
     */
    private $classes = [];
    private $files = [];
    private $output_directory;
    
    /**
     * Create a new parser instance.
     *
     * @param string $filename
     */
    public function __construct($apib, $output_directory = 'build')
    {
        $this->apib = $apib;
        $this->setOutputDirectory($output_directory);
    }
    
    public function getApib()
    {
        return $this->apib;
    }
    
    public function setNamespace($namespace)
    {
        $this->namespace = $namespace;
    }
    
    public function getNamespace()
    {
        return $this->namespace;
    }
    
    public function setOutputDirectory($path)
    {
        $this->output_directory = $path;
        if (!file_exists($path)) {
            mkdir($path, 0700, true);
        }
    }

    /**
     * @return \PhpParser\PrettyPrinter\Standard
     */
    private function getStandard()
    {
        if (null === $this->Standard) {
            $this->Standard = new Standard();
        }
        return $this->Standard;
    }

    /**
     * @return \PhpParser\BuilderFactory
     */
    private function getBuilderFactory()
    {
        if (null === $this->BuilderFactory) {
            $this->BuilderFactory = new BuilderFactory();
        }
        return $this->BuilderFactory;
    }

    /**
     * @param string $name
     * @return \PhpParser\Builder\Class_
     */
    public function setClass(string $name)
    {
        if (!array_key_exists($name, $this->classes)) {
            $factory = $this->getBuilderFactory();
            $this->classes[$name] = $factory->class($name);
        }
        return $this->classes[$name] ;
    }
    
    public function getClass($name)
    {
        if (array_key_exists($name, $this->classes)) {
            return $this->classes[$name];
        }
    }
    
    public function generate($node = null)
    {
        if (!$node) {
            $node = $this->apib;
        }
        $this->generateRequestClass($node);
        $this->generateMainClass();
    }
    
    public function generateMainClass()
    {
    }

    public function generateRequestClass(array $node)
    {
        $factory = $this->getBuilderFactory();
        $class = $this->setClass('Request')
            ->addStmt($factory->method('request')
                ->setDocComment(
                    "/**
                      * Send an request
                      *
                      * @param string \$method The method of HTTP request
                      * @param string \$url The path of endpoint to request
                      * @return array|Exception
                      */")
                ->makeProtected()
                ->addParam($factory
                    ->param('method')
                    ->setTypeHint('string'))
                ->addParam($factory
                    ->param('url')
                    ->setTypeHint('string'))
                ->setReturnType('array')
                ->addStmt(
                    //new Print_(new Variable('someParam'))
                    new Node\Expr\Assign(
                        new Node\Expr\Variable('client'),
                        new Node\Expr\New_(
                            new Node\Name\FullyQualified(['GuzzleHttp', 'Client']),
                            [
                                new Node\Expr\Array_(
                                    [
                                        new Node\Expr\ArrayItem(
                                            new Node\Expr\PropertyFetch(
                                                new Node\Expr\Variable('this'),
                                                'hots'
                                                ),
                                            new Node\Scalar\String_('base_uri')
                                            )
                                    ],
                                    ['kind' => Node\Expr\Array_::KIND_SHORT]
                                    )
                            ]
                            )
                        )
                    )
                ->addStmt(
                    new Node\Stmt\TryCatch(
                        [
                            new Node\Expr\Assign(
                                new Node\Expr\Variable('res'),
                                new Node\Expr\MethodCall(
                                    new Node\Expr\Variable('client'),
                                    'request',
                                    [
                                        new Node\Arg(
                                            new Node\Expr\Variable('method')
                                            ),
                                        new Node\Arg(
                                            new Node\Expr\Variable('url')
                                            )
                                    ]
                                    )
                                )
                        ],
                        [
                            new Node\Stmt\Catch_(
                                [new Node\Name('Exception')],
                                'e',
                                [
                                    new Node\Expr\Assign(
                                        new Node\Expr\Variable('res'),
                                        new Node\Expr\FuncCall(
                                            new Node\Name('json_decode'),
                                            [
                                                new Node\Arg(
                                                    new Node\Expr\MethodCall(
                                                        new Node\Expr\MethodCall(
                                                            new Node\Expr\MethodCall(
                                                                new Node\Expr\Variable('e'),
                                                                'getResponse'
                                                                ),
                                                            'getBody'
                                                            ),
                                                        'getContents'
                                                        )
                                                    )
                                            ]
                                            )
                                        )
                                ]
                                )
                        ]
                        )
                    )
                ->addStmt(
                    new Node\Stmt\Return_(
                        new Node\Expr\FuncCall(
                            new Node\Name('json_decode'),
                            [
                                new Node\Arg(
                                    new Node\Expr\MethodCall(
                                        new Node\Expr\MethodCall(
                                            new Node\Expr\Variable('res'),
                                            'getBody'
                                            ),
                                        'getContents'
                                        )
                                    ),
                                new Node\Arg(
                                    new Node\Expr\ConstFetch(
                                        new Node\Name('true')
                                        )
                                    )
                            ]
                            )
                        )
                    )
                );
        foreach ($node['attributes']['meta'] as $property) {
            $class
                ->addStmt($factory
                    ->property(strtolower($property['content']['key']['content']))
                    ->makeProtected()
                    ->setDefault($property['content']['value']['element'])
                    ->setDocComment(
                        "/**
                          * {$property['content']['key']['content']}
                          * @var {$property['content']['value']['content']}
                          */"
                        )
                    );
        }
    }

    public function printFiles()
    {
        $factory = $this->getBuilderFactory();
        $node = $factory->namespace($this->getNamespace());

        $prettyPrinter = $this->getStandard();
        $return = [];
        if ($this->classes) {
            foreach ($this->classes as $name => $class) {
                $file = clone $node;
                $file->addStmt($class);
                $this->files[$name] = $file;

                file_put_contents(
                    $return[] = $this->output_directory.$name.'.php',
                    $prettyPrinter->prettyPrintFile([$file->getNode()])."\n\n"
                );
            }
        }
        return $return;
    }
}
