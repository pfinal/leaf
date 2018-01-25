<?php

namespace Leaf\Console;

use Leaf\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpCommand extends Command
{
    //运行  php console up
    protected function configure()
    {
        $this->setName('up')
            ->setDescription('Bring the application out of maintenance mode');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        @unlink(Application::$app->getRuntimePath('down'));

        $output->writeln('Application is now live.');
    }
}