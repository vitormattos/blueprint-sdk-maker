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
            ->setDescription('Shows the short information about Blueprint SDK Maker.')            ->setDefinition([
                new InputArgument('apib', InputArgument::REQUIRED, 'Required apib file'),
                new InputOption('no-phar', null, InputOption::VALUE_NONE, 'Don\'t generate phar archive.')
            ])
            ->setHelp(<<<EOT
The <info>make</info> command reads the <info>.apib</info> file from the argument of command,
processes it, create new SDK files into directory <info>build</info> and create
a <info>phar</info> archive to use SDK standalone.

<info>php blueprint-sdk-maker.phar make <file.apib></info>

EOT
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!shell_exec('which drafter')) {
            die();
            $drafterBin = $input->getParameterOption('--drafter-bin');
            if (!$drafterBin) {
                $output->writeln(
                    "<error>The drafter command is mandatory</error>\n".
                    "Install drafter or specify the binary. Access https://github.com/apiaryio/drafter to read\n".
                    "about install drafter or inform the location of drafter binary by <info>--drafter-bin</info> argument</error>"
                    );
            }
            return 1;
        } else {
            $drafterBin = trim(shell_exec('which drafter'));
        }

        $apib = $input->getArgument('apib');
        if (!is_file($apib)) {
            $output->writeln('<error>invalid apib file.</error>');
            return 1;
        }

        $apib = file_get_contents($apib);
        $result = Parser::parse($apib, 'json', true, $drafterBin);
    }
}
