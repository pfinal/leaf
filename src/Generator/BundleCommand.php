<?php

namespace Leaf\Generator;

use Leaf\View;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

class BundleCommand extends Command
{
    protected $name = 'make:bundle';
    protected $description = 'create a new bundle';

    protected function configure()
    {
        $this->setName($this->name)
            ->setDescription($this->description)
            ->setDefinition(array(//new InputOption('name', null, InputOption::VALUE_OPTIONAL, 'bundle name'),

            ));
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->write("BundleName:");
        $bundleName = trim(fgets(STDIN));

        $bundlePath = realpath('./src') . DIRECTORY_SEPARATOR . $bundleName . DIRECTORY_SEPARATOR;

        if (file_exists($bundlePath)) {
            $output->writeln($bundlePath . ' exist.');
            return;
        }

        @mkdir($bundlePath, 0775);

        @mkdir($bundlePath . 'Controller', 0775);

        @mkdir($bundlePath . 'Service', 0775);
        touch($bundlePath . 'Service' . DIRECTORY_SEPARATOR . '.gitkeep');

        @mkdir($bundlePath . 'resources', 0775);
        @mkdir($bundlePath . 'resources' . DIRECTORY_SEPARATOR . 'views', 0775);
        file_put_contents(
            $bundlePath . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'layout.twig',
            '{% extends "admin.twig" %}');

        file_put_contents(
            $bundlePath . 'resources' . DIRECTORY_SEPARATOR . 'routes.php',
            "<?php\n\nuse Leaf\Route;\n\nRoute::group(['middleware' => ['auth', 'csrf']], function () {
    
});");

        file_put_contents(
            $bundlePath . $bundleName . '.php',
            View::renderText(file_get_contents(__DIR__ . '/tpl/bundle.twig'), [
                'bundleName' => $bundleName,
            ])
        );

        touch($bundlePath . 'Controller' . DIRECTORY_SEPARATOR . '.gitkeep');

        $output->writeln('');
        $output->writeln('please register bundle:');
        $output->writeln('$app->registerBundle(new \\' . $bundleName . '\\' . $bundleName . '());');
        $output->writeln('');
        $output->writeln('SUCCESS');
        $output->writeln('');
    }
}
