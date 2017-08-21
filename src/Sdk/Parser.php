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
    private $coreFolder = 'Core';
    private $entityFolder = 'Entity';
    private $format;

    /**
     * Create a new parser instance.
     *
     * @param string $filename
     */
    public function __construct($config)
    {
        $config = array_merge([
            'apib' => null,
            'format' => 'json-array',
            'output_directory' => 'build'
        ], $config);
        $this->apib = $config['apib'];
        $this->format = $config['format'];
        $this->setOutputDirectory($config['output_directory']);
    }

    public function getApib()
    {
        return $this->apib;
    }

    public function getCoreFolder()
    {
        return $this->coreFolder;
    }

    public function getEntityFolder()
    {
        return $this->entityFolder;
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
    public function setClass(string $name, $namespace = null)
    {
        if (!array_key_exists($name, $this->classes)) {
            $factory = $this->getBuilderFactory();
            $this->classes[$name]['node'] = $factory->class($name);
            if (!$namespace) {
                $namespace = $this->getNamespace();
            }
            $this->classes[$name]['namespace'] = $namespace;
        }
        return $this->classes[$name]['node'];
    }

    public function generate()
    {
        $this->generateRequestClass($this->apib);
        $this->generateMainClass($this->apib);
        $this->generateEntity($this->apib);
    }

    private function generateEntity($node)
    {
        $Parser = $this->getParser();
        $factory = $this->getBuilderFactory();
        if (isset($node['content'])) {
            foreach ($node['content'] as $entity) {
                if ($entity['element'] == 'category') {
                    $className = ucwords($this->getNode($entity['meta']['title']));
                    $className = str_replace(' ', '', $className);
                    $class = $this->setClass($className, $this->getNamespace().'\\'.$this->getEntityFolder());
                    $class->extend('\\'.$this->getNamespace().'\\'.$this->getCoreFolder().'\\Request');
                    foreach ($entity['content'] as $endpoint) {
                        $endpointName = ucwords($this->getNode($endpoint['meta']['title']));
                        $endpointName = lcfirst($endpointName);
                        $endpointName = str_replace(' ', '', $endpointName);
                        $method = $factory->method($endpointName);

                        $methodDescription = [];
                        if (isset($endpoint['content'][0]['meta']['title'])) {
                            $methodDescription[] = $this->getNode($endpoint['content'][0]['meta']['title']);
                            $methodDescription[] = '';
                        }

                        if (isset($endpoint['content'][0]['content'][0]['content'][0]['attributes']['method'])) {
                            $httpVerb = $endpoint['content'][0]['content'][0]['content'][0]['attributes']['method'];
                        } else {
                            $httpVerb = 'get';
                        }
                        $method->addStmts($Parser->parse(
                            '<?php $method = \''.strtolower($this->getNode($httpVerb)).'\';'
                        ));
                        if (isset($endpoint['content'][0]['attributes']['hrefVariables']['content'])) {
                            $args = $endpoint['content'][0]['attributes']['hrefVariables']['content'];
                        } elseif (isset($endpoint['attributes']['hrefVariables']['content'])) {
                            $args = $endpoint['attributes']['hrefVariables']['content'];
                        } else {
                            $args = [];
                        }
                        foreach ($args as $arg) {
                            $docParam = '@param';
                            $endpointParam = $factory->param($arg['content']['key']['content']);
                            if (isset($arg['meta']['title'])) {
                                $endpointParam->setTypeHint($this->getNode($arg['meta']['title']));
                                $docParam.= ' '.$this->getNode($arg['meta']['title']);
                            }
                            $typeAttribytes = $this->getNode($arg['attributes']['typeAttributes']);
                            if ($this->getNode($typeAttribytes[0]) != 'required') {
                                $docParam.= ' (optional)';
                                $endpointParam->setDefault(null);
                            }
                            if (isset($arg['meta']['description'])) {
                                $docParam .= $this->getNode($arg['meta']['description']);
                            }
                            $method->addParam($endpointParam);
                            $methodDescription[] = $docParam;
                        }

                        $url = $this->convertUrl($this->getNode($endpoint['attributes']['href']));
                        $url = parse_url($url);
                        $url = $url['path'];
                        $code = [];
                        $code[]= '$path = "'.$url.'";';
                        $hasOptional = false;
                        if (isset($endpoint['content'][0]['attributes']['hrefVariables']['content'])) {
                            foreach ($endpoint['content'][0]['attributes']['hrefVariables']['content'] as $arg) {
                                $name = $this->getNode($this->getNode($arg)['key']);
                                if (isset($arg['meta']['title'])) {
                                    $type = $this->getNode($arg['meta']['title']);
                                    if ($type == 'boolean') {
                                        $code[] =
                                        "if(\$$name == 'true' || \$$name == '1'){".
                                        "$$name = true;".
                                        "} else { $$name = false; }";
                                    }
                                }
                                $typeAttribytes = $this->getNode($arg['attributes']['typeAttributes']);
                                if ($this->getNode($typeAttribytes[0]) != 'required') {
                                    $hasOptional = true;
                                    $code[]=
                                    "if(!is_null(\$$name)) {".
                                        "\$params['$name'] = \$$name;".
                                    "}";
                                }
                            }
                            if ($hasOptional) {
                                $code[]= '$path.= \'?\'.http_build_query($params);';
                            }
                        }
                        $method->addStmts($Parser->parse('<?php '.implode("\n", $code)));

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
    }

    private function getNode($node, $type = 'content')
    {
        if (is_array($node) && isset($node[$type])) {
            $node = $node[$type];
        }
        return $node;
    }

    private function generateMainClass(array $node)
    {
        $factory = $this->getBuilderFactory();
        $Parser = $this->getParser();
        $class = $this->setClass('Api', $this->getNamespace().'\\'.$this->getCoreFolder());
        $constructor = $factory->method('__construct')
            ->makePublic();
        if (isset($node['content'])) {
            foreach ($node['content'] as $endpoint) {
                if ($endpoint['element'] == 'category') {
                    $propertyName = ucwords($this->getNode($endpoint['meta']['title']));
                    $propertyName = str_replace(' ', '', $propertyName);
                    $constructor->addStmts($Parser->parse(
                        '<?php $this->'.$propertyName.' = new \\'.$this->getNamespace().'\\'.$this->getEntityFolder().'\\'.$propertyName.'($this->host);'
                    ));
                    $class
                        ->addStmt($factory
                        ->property($propertyName)
                        ->setDocComment(
                            "/**
                             * $propertyName
                            * @var \\{$this->getNamespace()}\\{$this->getEntityFolder()}\\$propertyName
                             */"
                        ));
                }
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
                **/"
            )
            ->addStmts($Parser->parse('<?php $this->host = $host;')));

        $properties = [];
        $attributes = $this->getNode($node, 'attributes');
        if ($attributes) {
            $metadata = $this->getNode($attributes, 'meta');
            if (!$metadata) {
                $metadata = $this->getNode($attributes, 'metadata');
            }
            if ($metadata) {
                foreach ($metadata as $property) {
                    $property = $this->getNode($property);
                    if (!isset($property['content'])) {
                        continue;
                    }
                    $name = strtolower($property['content']['key']['content']);
                    $properties[] = $name;
                    $class->addStmt($factory
                        ->property($name)
                        ->makeProtected()
                        ->setDefault($property['content']['value']['content'])
                        ->setDocComment(
                            "/**
                            * {$property['content']['key']['content']}
                            * @var {$property['content']['value']['content']}
                            */"
                        ));
                }
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

    private function generateRequestClass(array $node)
    {
        $Parser = $this->getParser();
        $content = "<?php
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
        \$client = new \GuzzleHttp\Client(['base_uri' => \$this->host]);
        try {
            \$res = \$client->request(\$method, \$url);
        } catch (Exception \$e) {
            \$res = {$this->formatRequestClassOutput('$e->getResponse()->getBody()->getContents()')};
        }
        return {$this->formatRequestClassOutput('$res->getBody()->getContents()')};
    }
}
";
        $tmp = $Parser->parse($content)[0];
        $class = $this->setClass($tmp->name, $this->getNamespace().'\\'.$this->getCoreFolder());
        $class->addStmts($tmp->stmts);
    }

    private function formatRequestClassOutput($string)
    {
        if ($this->format == 'json-array') {
            $return = 'json_decode('.$string.', true)';
        } elseif ($this->format == 'json-object') {
            $return = 'json_decode('.$string.')';
        } else {
            $return = $string;
        }
        return $return;
    }

    public function printFiles()
    {
        $factory = $this->getBuilderFactory();

        $prettyPrinter = $this->getStandard();
        $return = [];
        if ($this->classes) {
            foreach ($this->classes as $name => $class) {
                $file = $factory->namespace($class['namespace']);
                $file->addStmt($class['node']);
                $this->files[$name] = $file;

                $dir = str_replace(
                    $this->getNamespace(),
                    '',
                    $class['namespace']
                );
                $dir = str_replace('\\', DIRECTORY_SEPARATOR, $dir);
                $dir = $this->output_directory.$dir.DIRECTORY_SEPARATOR;
                $dir = str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $dir);
                if (!is_dir($dir)) {
                    mkdir($dir);
                }
                file_put_contents(
                    $return[] = $dir.$name.'.php',
                    $prettyPrinter->prettyPrintFile([$file->getNode()])."\n\n"
                );
            }
        }
        return $return;
    }

    private function convertUrl($url)
    {
        if (preg_match('/\{\?[a-z,]+\}/', $url, $matches)) {
            $url = str_replace('?', '', $url);
            $url = str_replace(',', '}{', $url);
        }
        $url = str_replace('{', '{$', $url);
        return $url;
    }
}
