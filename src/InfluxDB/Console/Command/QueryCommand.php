<?php
namespace InfluxDB\Console\Command;

use InfluxDB\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class QueryCommand extends Command
{
    protected function configure()
    {
        $this->setName('query');
        $this->addOption("db", "d", InputOption::VALUE_REQUIRED, "", "database name");
        $this->addOption("execute", "e", InputOption::VALUE_REQUIRED, "", "query");
        $this->addOption('host', null, InputOption::VALUE_REQUIRED, "", '127.0.0.1');
        $this->addOption('port', null, InputOption::VALUE_REQUIRED, "", 8086);
        $this->addOption('user', "u", InputOption::VALUE_REQUIRED, "", 'root');
        $this->addOption('password', "p", InputOption::VALUE_REQUIRED, "", 'root');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setHelperSet($this->getApplication()->getHelperSet());

        $db = $input->getOption("db");
        $query = $input->getOption("execute");
        $port = $input->getOption("port");
        $host = $input->getOption("host");
        $user = $input->getOption("user");
        $password = $input->getOption("password");
        $verbose = $input->getOption("verbose");

        $c = new Client(sprintf("http://%s:%s", $host, $port), $user, $password, $db);
        if ($verbose) {
            $c->setDebug(true);
        }
        $dialog = $this->getHelperSet()->get('dialog');

        try {
            ///$query = $dialog->ask($output, sprintf('influx: %s> ', $db));

            $begin = microtime(true);
            $result = $c->query($query);
            $end = microtime(true);

            if (empty($result)) {
                $output->writeln(sprintf("Empty set (%f sec)", round($end - $begin, 4)));
                return;
            }
            $total = 0;
            foreach ($result as $chunk) {
                $keys = $chunk->getColumns();
                $table = $this->getHelperSet()->get('table');
                $table->setHeaders($keys);
                $table->setRows($chunk->getPoints());
                $table->render($output);
                $total += count($chunk);
            }

            $output->writeln(sprintf("%d rows in set (%f sec)", $total, round($end - $begin, 4)));

        } catch (\Exception $e) {
            $output->writeln($e->getMessage());
        }
    }
}