<?php

namespace BlueprintSdkMaker\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

class AboutCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('about')
            ->setDescription('Shows the short information about Blueprint SDK Maker.')
            ->setHelp(<<<EOT
<info>php blueprint-sdk-maker.phar about</info>
EOT
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->write(<<<EOT
<info>Blueprint SDK Maker - Create SDK client from API blueprint apib file</info>
<comment>API Blueprint is a powerful high-level API description language for web APIs.
With this command you will parse doc from API Blueprint and generate a PHP SKD.
See https://github.com/vitormattos/blueprint-sdk-maker/ for more information.</comment>

EOT
        );
    }
}
