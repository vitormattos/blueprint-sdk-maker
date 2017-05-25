<?php
namespace BlueprintSdkMaker;

use PhpParser\PrettyPrinter\Standard;
use PhpParser\BuilderFactory;
use PhpParser\Node;
use PhpParser\ParserFactory;

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
     * @see \PhpParser\ParserFactory
     *
     * @var \PhpParser\Parser
     */
    private $Parser;
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
    
    private function getParser()
    {
        if (null === $this->Parser) {
            $this->Parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        }
        return $this->Parser;
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
        $this->generateMainClass($node);
        $this->generateEntity($node);
    }
    
    private function generateEntity($node)
    {
        $Parser = $this->getParser();
        $factory = $this->getBuilderFactory();
        foreach ($node['content'] as $entity) {
            if ($entity['element'] == 'category') {
                $className = ucwords($entity['meta']['title']);
                $className = str_replace(' ', '', $className);
                $class = $this->setClass($className);
                $class->extend('Request');
                foreach ($entity['content'] as $endpoint) {
                    $endpointName = ucwords($endpoint['meta']['title']);
                    $endpointName = lcfirst($endpointName);
                    $endpointName = str_replace(' ', '', $endpointName);
                    $method = $factory->method($endpointName);
                    $method->addStmt(
                        new Node\Expr\Assign(
                            new Node\Expr\Variable('method'),
                            new Node\Scalar\String_(
                                strtolower($endpoint['content'][0]['content'][0]['content'][0]['attributes']['method'])
                                )
                            )
                        );
                    $methodDescription = [];
                    if (isset($endpoint['content'][0]['meta']['title'])) {
                        $methodDescription[] = $endpoint['content'][0]['meta']['title'];
                        $methodDescription[] = '';
                    }
                    $hasOptional = false;
                    if (isset($endpoint['content'][0]['attributes']['hrefVariables']['content'])) {
                        foreach ($endpoint['content'][0]['attributes']['hrefVariables']['content'] as $arg) {
                            $docParam = '@param';
                            $endpointParam = $factory->param($arg['content']['key']['content']);
                            if (isset($arg['meta']['title'])) {
                                $endpointParam->setTypeHint($arg['meta']['title']);
                                $docParam.= ' '.$arg['meta']['title'];
                            }
                            if ($arg['attributes']['typeAttributes'][0] != 'required') {
                                $hasOptional = true;
                                $docParam.= ' (optional)';
                                $endpointParam->setDefault(null);
                            }
                            if (isset($arg['meta']['description'])) {
                                $docParam.=$arg['meta']['description'];
                            }
                            $method->addParam($endpointParam);
                            $methodDescription[] = $docParam;
                        }
                    }
                    if ($hasOptional) {
                        $url = parse_url($endpoint['attributes']['href']);
                        $url['path'] = str_replace('{', '{$', $url['path']);
                        $code = [];
                        $code[]= '$path = "'.$url['path'].'";';
                        foreach ($endpoint['content'][0]['attributes']['hrefVariables']['content'] as $arg) {
                            if ($arg['attributes']['typeAttributes'][0] != 'required') {
                                $name = $arg['content']['key']['content'];
                                $code[]="if(!is_null(\$$name)) \$params['$name'] = \$$name;";
                            }
                        }
                        $code[]= '$path.= \'?\'.http_build_query($params);';
                        $method->addStmts($Parser->parse('<?php '.implode("\n", $code)));
                    } else {
                        $url = str_replace('{', '{$', $endpoint['attributes']['href']);
                        $method->addStmts($Parser->parse("<?php \$path = \"$url\";"));
                    }
                    $method->addStmts($Parser->parse('<?php return self::request($method, $path);'));
                    $methodDescription[] = '@return Array|Exception array';
                    $method->setDocComment(
                        "/**\n".
                        "     * ".
                        implode("\n     * ", $methodDescription).
                        "\n     */"
                        );
                    $method->setReturnType('array');
                    $class
                        ->addStmt($method);
                }
            }
        }
    }
    
    private function generateMainClass(array $node)
    {
        $factory = $this->getBuilderFactory();
        $class = $this->setClass('Api');
        $constructor = $factory->method('__construct')
            ->makePublic();
        foreach ($node['content'] as $endpoint) {
            if ($endpoint['element'] == 'category') {
                $propertyName = ucwords($endpoint['meta']['title']);
                $propertyName = str_replace(' ', '', $propertyName);
                $constructor->addStmt(
                    new Node\Expr\Assign(
                        new Node\Expr\PropertyFetch(
                            new Node\Expr\Variable('this'),
                            $propertyName
                        ),
                        new Node\Expr\New_(new Node\Name([$propertyName]))
                    )
                );
                $class
                    ->addStmt($factory
                        ->property($propertyName)
                        ->setDocComment(
                            "/**
                             * $propertyName
                             * @var $propertyName
                             */"));
            }
        }
        $class->addStmt($constructor);
        $Parser = $this->getParser();
        $class->addStmt($factory->method('setHost')
            ->addParam($factory->param('host')
                ->setDefault(null))
            ->setDocComment(
                "/**
                * Define the base URL to API
                * @param string \$host
                **/")
            ->addStmts($Parser->parse('<?php $this->host = $host;')));
    }

    private function generateRequestClass(array $node)
    {
        $Parser = $this->getParser();
        $tmp = $Parser->parse("<?php
class Request
{
    public function __construct(\$host = null) {
        if (\$host) {
            \$this->host = \$host;
        }
    }
    /**
     * Send an request
     *
     * @param string \$method The method of HTTP request
     * @param string \$url The path of endpoint to request
     * @return array|Exception
     */
    protected function request(string \$method, string \$url) : array
    {
        \$client = new \GuzzleHttp\Client(['base_uri' => \$this->hots]);
        try {
            \$res = \$client->request(\$method, \$url);
        } catch (Exception \$e) {
            \$res = json_decode(\$e->getResponse()->getBody()->getContents());
        }
        return json_decode(\$res->getBody()->getContents(), true);
    }
}
")[0];
        $class = $this->setClass($tmp->name);
        $class->addStmts($tmp->stmts);
        
        $factory = $this->getBuilderFactory();
        $properties = [];
        if (isset($node['attributes']['meta'])) {
            foreach ($node['attributes']['meta'] as $property) {
                $name = strtolower($property['content']['key']['content']);
                $properties[] = $name;
                $class->addStmt($factory
                    ->property($name)
                    ->makeProtected()
                    ->setDefault($property['content']['value']['element'])
                    ->setDocComment(
                        "/**
                          * {$property['content']['key']['content']}
                          * @var {$property['content']['value']['content']}
                          */"
                        ));
            }
        }
        if (!in_array('host', $properties)) {
            $class->addStmt($factory
                ->property('host')
                ->makeProtected()
                ->setDocComment(
                    "/**
                      * Base url to API
                      * @var string
                      */"
                ));
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
