<?php

namespace Leaf\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateCreate extends \Phinx\Console\Command\Create
{
    protected function configure()
    {
        parent::configure();
        $this->setName('migrate:create');
    }

    protected function getMigrationTemplateFilename()
    {
        return __DIR__ . '/Migration.template.php.dist';
    }
}