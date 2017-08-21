<?php

namespace BlueprintSdkMaker\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Oasis\Parser;

class MakeCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('make')
            ->setDescription('Shows the short information about Blueprint SDK Maker.')
            ->setDefinition([
                new InputArgument('apib-file', InputArgument::REQUIRED, 'Required apib file'),
                new InputOption('no-phar', null, InputOption::VALUE_NONE, 'Don\'t generate phar archive.'),
                new InputOption(
                    'format',
                    null,
                    InputArgument::OPTIONAL,
                    "The output format returned by endpoints\n".
                    "<info>raw</info>: return raw output from all endpoints\n".
                    "<info>json-array</info>: Expect json response and convert to array\n".
                    "<info>json-object</info>: Expect json response and convert to object)\n",
                    'json-array'
                ),
                new InputOption('directory', 'd', InputArgument::OPTIONAL, 'Directory where to generate files', 'build'),
                new InputOption('namespace', 's', InputArgument::OPTIONAL, 'Namespace prefix to use for generated files', 'BlueprintSdk'),
                new InputOption('drafter-bin', null, InputArgument::OPTIONAL, 'Binary of Drafter', 'drafter')
            ])
            ->setHelp(<<<EOT
The <info>make</info> command reads the <info>.apib</info> file from the argument of command,
processes it, create new SDK files into specific directory (<info>build</info> is default)
and create a <info>phar</info> archive to use SDK standalone.

<info>php blueprint-sdk-maker.phar make <file.apib></info>

EOT
            )
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $options['drafter'] = $input->getOption('drafter-bin');
        $options['directory'] = $input->getOption('directory');
        $options['namespace'] = $input->getOption('namespace');
        $options['no-phar'] = $input->getOption('no-phar');
        $options['format'] = $input->getOption('format');
        $options['verbose'] = $output->getVerbosity();
        $options['apib-file'] = $input->getArgument('apib-file');

        if (!shell_exec('which '.$options['drafter'])) {
            $output->writeln(
                "<error>The drafter command is mandatory</error>\n".
                "Install drafter or specify the binary. Access https://github.com/apiaryio/drafter to read\n".
                "about install drafter or inform the location of drafter binary by <info>--drafter-bin</info> argument</error>"
            );
            return 1;
        }

        if (!is_file($options['apib-file'])) {
            $output->writeln('<error>invalid apib file.</error>');
            return 1;
        }
        if (!$options['no-phar'] && ini_get('phar.readonly')) {
            $output->writeln('<error>Enable phar.readonly into php.ini setting phar.readonly to 0.</error>');
            return 1;
        }

        $apib = file_get_contents($options['apib-file']);
        $result = Parser::parse($apib, 'json', true, $options['drafter']);

        $SdkMaker = new \BlueprintSdkMaker\Parser([
            'apib' => $result['content'][0],
            'output_directory' => $options['directory'].'/src/',
            'format' => $options['format']
        ]);
        $SdkMaker->setNamespace($options['namespace']);
        $SdkMaker->generate();
        $paths = $SdkMaker->printFiles();

        $composerJson = <<<EOT
{
    "autoload" : {
        "psr-4" : {
            "{$options['namespace']}\\\\" : "src"
        }
    },
    "require" : {
        "guzzlehttp/guzzle" : "^6.2"
    },
    "require-dev" : {
        "phpunit/phpunit" : "^6.1"
    }
}
EOT;
        file_put_contents(
            $paths[] = $options['directory'].DIRECTORY_SEPARATOR.'composer.json',
            $composerJson
        );
        copy('LICENSE', $options['directory'].DIRECTORY_SEPARATOR.'LICENSE');
        copy('res/README.md', $options['directory'].DIRECTORY_SEPARATOR.'README.md');
        copy('res/composer.lock', $options['directory'].DIRECTORY_SEPARATOR.'composer.lock');
        copy('res/phpunit.xml.dist', $options['directory'].DIRECTORY_SEPARATOR.'phpunit.xml.dist');
        if (!is_dir($options['directory'].DIRECTORY_SEPARATOR.'tests')) {
            mkdir($options['directory'].DIRECTORY_SEPARATOR.'tests');
        }
        copy('res/tests/phpunit-bootstrap.php', $options['directory'].DIRECTORY_SEPARATOR.'tests'.DIRECTORY_SEPARATOR.'phpunit-bootstrap.php');

        if (!$options['no-phar']) {
            exec(
                'composer install'.
                ' -d '.$options['directory'].
                ' --prefer-dist'.
                ' --no-dev'.
                ($options['verbose'] > OutputInterface::VERBOSITY_NORMAL
                    ?' -vvv'
                    :' --quiet'
                )
            );
            $cwd = getcwd();
            chdir($options['directory']);
            $phar = new \Phar('api.phar', 0, 'api.phar');
            $addDirectory = function ($directory) use ($phar, $options) {
                $di = new \RecursiveDirectoryIterator($directory);
                foreach (new \RecursiveIteratorIterator($di) as $filename => $file) {
                    if (preg_match('_/\.\.?$_', $filename) || # .. and .
                        preg_match('_/\.git/_', $filename)    # .git
                        ) {
                            continue;
                    }
                    if ($options['verbose'] > OutputInterface::VERBOSITY_NORMAL) {
                        echo "Adding file: $filename\n";
                    }
                    $phar->addFile($filename);
                }
            };
            $addDirectory('src');
            $addDirectory('vendor');
            $phar->setStub("<?php Phar::mapPhar('api.phar'); include('phar://api.phar/vendor/autoload.php'); __HALT_COMPILER();");
            chdir($cwd);
        }

        foreach ($paths as $name) {
            $output->writeln(sprintf("Generate %s", $name));
        }
    }
}
