<?php
namespace InfluxDB\Console;

use InfluxDB\Console\Command\InfluxCommand;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use InfluxDB\Console\Command\QueryCommand;

class Application extends BaseApplication
{
    public function __construct($version)
    {
        parent::__construct("influx", $version);
    }

    protected function getCommandName(InputInterface $input)
    {
        return 'query';
    }

    protected function getDefaultCommands()
    {
        $defaultCommands = parent::getDefaultCommands();
        $defaultCommands[] = new QueryCommand();
        return $defaultCommands;
    }

    public function getDefinition()
    {
        $inputDefinition = parent::getDefinition();
        $inputDefinition->setArguments();
        return $inputDefinition;
    }
}