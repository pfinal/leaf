<?php

namespace Leaf\Generator;

use Leaf\Application;
use Leaf\DB;
use Leaf\View;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

class ApiCommand extends CurdCommand
{
    protected $name = 'make:api';
    protected $description = 'create a api controller that implement CURD (Create, Update, Read, Delete)';

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->write("BundleName:");
        $bundleName = trim(fgets(STDIN));

        $bundle = Application::$app->getBundle($bundleName);

        $output->write("EntityName:");
        $fullEntityName = trim(fgets(STDIN));

        //命名空间
        if (($ind = strrpos($fullEntityName, '\\')) !== false) {
            $entityName = substr($fullEntityName, $ind + 1);
            $entityNamespace = substr($fullEntityName, 0, strlen($fullEntityName) - strlen($entityName));
        } else {
            $entityName = $fullEntityName;
            $entityNamespace = 'Entity\\';
        }

        //获取表名 User::tableName()
        $tableName = call_user_func(array($entityNamespace . $entityName, 'tableName'));

//        //去掉占位符 例如 "{{%user}}" 得到 "user"
//        $tableName = preg_replace_callback(
//            '/(\\{\\{(%?[\w\-\.\$ ]+%?)\\}\\}|\\[\\[([\w\-\. ]+)\\]\\])/',
//            function ($matches) {
//                if (isset($matches[3])) {
//                    return $matches[3];
//                } else {
//                    return str_replace('%', '', $matches[2]);
//                }
//            },
//            $tableName
//        );

        $middleName = $this->convertToMiddle($entityName);
        $bundlePath = $bundle->getPath() . DIRECTORY_SEPARATOR;

        //AppBundle不使用Url前缀
        if ($bundleName == 'AppBundle') {
            $bundleMiddleName = '';
        } else {
            $bundleMiddleName = $this->convertToMiddle(substr($bundleName, 0, strlen($bundleName) - 6), false) . '/';
        }

        $tableComment = self::getTableComment($tableName, $entityName);

        $attributes = $allAttributes = self::getField($tableName);
        unset($attributes['created_at']);
        unset($attributes['updated_at']);

        $attributesNoId = $attributes;
        unset($attributesNoId['id']);

        $checkList = [
            $bundlePath . 'Controller' . DIRECTORY_SEPARATOR . $entityName . 'Controller.php',
        ];
        foreach ($checkList as $file) {
            if (file_exists($file)) {
                $output->writeln($file . ' exist.');
                return;
            }
        }

        file_put_contents(
            $bundlePath . 'Controller' . DIRECTORY_SEPARATOR . $entityName . 'Controller.php',
            View::renderText(file_get_contents(__DIR__ . '/tpl/api.twig'), [
                'bundleName' => $bundleName,
                'entityName' => $entityName,
                'middleName' => $middleName,
                'bundleMiddleName' => $bundleMiddleName,
                'tableComment' => $tableComment,
                'attributes' => $attributesNoId,
                'allAttributes' => $allAttributes,
                'entityNamespace' => $entityNamespace,
            ])
        );

        $output->writeln('');
        $output->writeln('Please add route:');
        $output->writeln("Route::annotation('" . $bundleName . "\\Controller\\" . $entityName . "Controller');");
        $output->writeln('');
        $output->writeln('SUCCESS');
        $output->writeln('');
    }

}
