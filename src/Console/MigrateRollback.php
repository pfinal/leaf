<?php

namespace Leaf\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateRollback extends \Phinx\Console\Command\Rollback
{
    protected function configure()
    {
        parent::configure();
        $this->setName('migrate:rollback');
    }
}