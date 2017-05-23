<?php
namespace BlueprintSdkMaker\Console;

use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use BlueprintSdkMaker\Command\AboutCommand;
use Symfony\Component\Console\Input\InputOption;
use BlueprintSdkMaker\Command\MakeCommand;

/**
 * The console application that handles the commands
 */
class Application extends BaseApplication
{
    /**
     * {@inheritDoc}
     */
    public function doRun(InputInterface $input, OutputInterface $output)
    {
        if (!shell_exec('which drafter')) {
            $drafterBin = $input->getParameterOption('--drafter-bin');
            if (!$drafterBin) {
                $output->writeln(
                    "<error>The drafter command is mandatory</error>\n".
                    "Install drafter or specify the binary. Access https://github.com/apiaryio/drafter to read\n".
                    "about install drafter or inform the location of drafter binary by <info>--drafter-bin</info> argument</error>"
                );
            }
            return 1;
        }
        if (PHP_VERSION_ID < 70000) {
            $output->writeln('<error>Only supports PHP 7 and above, upgrading is strongly recommended.</error>');
        }

        $result = parent::doRun($input, $output);
    }
    
    public function getHelp()
    {
        return "\nBlueprint API Maker\n\n" . parent::getHelp();
    }
    
    /**
     * Initializes all commands.
     */
    protected function getDefaultCommands()
    {
        $commands = array_merge(parent::getDefaultCommands(), [
            new AboutCommand(),
            new MakeCommand()
        ]);

        return $commands;
    }
    
    /**
     * {@inheritDoc}
     */
    protected function getDefaultInputDefinition()
    {
        $definition = parent::getDefaultInputDefinition();
        $definition->addOption(new InputOption('drafter-bin', null, InputOption::VALUE_NONE, 'Binary of Drafter'));
        
        return $definition;
    }
}
