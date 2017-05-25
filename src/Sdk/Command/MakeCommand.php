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
                new InputOption('directory', 'd', InputArgument::OPTIONAL, 'Directory where to generate files', 'build'),
                new InputOption('namespace', 's', InputArgument::OPTIONAL, 'Namespace prefix to use for generated files', 'BlueprintApi'),
                new InputOption('drafter-bin', null, InputOption::VALUE_NONE, 'Binary of Drafter')
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
        $options['drafter'] = $input->getParameterOption('--drafter-bin');
        if (!shell_exec('which drafter') && !$options['drafter']) {
            $output->writeln(
                "<error>The drafter command is mandatory</error>\n".
                "Install drafter or specify the binary. Access https://github.com/apiaryio/drafter to read\n".
                "about install drafter or inform the location of drafter binary by <info>--drafter-bin</info> argument</error>"
                );
            return 1;
        } else {
            $options['drafter'] = trim(shell_exec('which drafter'));
        }

        $options['directory'] = $input->getOption('directory');
        $options['namespace'] = $input->getOption('namespace');

        $options['apib-file'] = $input->getArgument('apib-file');
        if (!is_file($options['apib-file'])) {
            $output->writeln('<error>invalid apib file.</error>');
            return 1;
        }

        $apib = file_get_contents($options['apib-file']);
        $result = Parser::parse($apib, 'json', true, $options['drafter']);

        $SdkMaker = new \BlueprintSdkMaker\Parser(
            $result['content'][0],
            $options['directory'].'/src/'
        );

        $SdkMaker->setNamespace($options['namespace']);
        $SdkMaker->generate();

        $paths = $SdkMaker->printFiles();

        foreach ($paths as $name) {
            $output->writeln(sprintf("Generate %s", $name));
        }
    }
}
