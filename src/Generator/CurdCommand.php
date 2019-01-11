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

class CurdCommand extends Command
{
    protected $name = 'make:curd';
    protected $description = 'create a controller and views that implement CURD (Create, Update, Read, Delete)';

    protected function configure()
    {
        $this->setName($this->name)
            ->setDescription($this->description);
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->write("BundleName:");
        $bundleName = trim(fgets(STDIN));

        $bundle = Application::$app->getBundle($bundleName);

        //$output->write("TableName:");
        //$tableName = trim(fgets(STDIN));
        //$entityName = $this->convert($tableName, false);

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
            //$bundlePath . 'Service' . DIRECTORY_SEPARATOR . $entityName . 'Service.php',
            $bundlePath . 'Controller' . DIRECTORY_SEPARATOR . $entityName . 'Controller.php',
            $bundlePath . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $middleName,
        ];
        foreach ($checkList as $file) {
            if (file_exists($file)) {
                $output->writeln($file . ' exist.');
                return;
            }
        }

        /*file_put_contents(
            $bundlePath . 'Service' . DIRECTORY_SEPARATOR . $entityName . 'Service.php',
            View::renderText(file_get_contents(__DIR__ . '/tpl/service.twig'), [
                'bundleName' => $bundleName,
                'entityName' => $entityName,
                'attributes' => $attributesNoId,
                'bundleMiddleName' => $bundleMiddleName,
                'tableComment' => $tableComment,
            ])
        );*/

        file_put_contents(
            $bundlePath . 'Controller' . DIRECTORY_SEPARATOR . $entityName . 'Controller.php',
            View::renderText(file_get_contents(__DIR__ . '/tpl/controller.twig'), [
                'bundleName' => $bundleName,
                'entityName' => $entityName,
                'middleName' => $middleName,
                'bundleMiddleName' => $bundleMiddleName,
                'tableComment' => $tableComment,
                'attributes' => $attributesNoId,
                'entityNamespace' => $entityNamespace,
            ])
        );

        @mkdir($bundlePath . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $middleName, 0775);

        file_put_contents(
            $bundlePath . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $middleName . DIRECTORY_SEPARATOR . 'create.twig',
            $this->renderPHP((__DIR__ . '/tpl/views/create.twig'), [
                'bundleName' => $bundleName,
                'entityName' => $entityName,
                'middleName' => $middleName,
                'attributes' => $attributes,
                'bundleMiddleName' => $bundleMiddleName,
                'tableComment' => $tableComment,
            ])
        );

        file_put_contents(
            $bundlePath . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $middleName . DIRECTORY_SEPARATOR . '_form.twig',
            $this->renderPHP((__DIR__ . '/tpl/views/_form.twig'), [
                'bundleName' => $bundleName,
                'entityName' => $entityName,
                'middleName' => $middleName,
                'attributes' => $attributes,
                'bundleMiddleName' => $bundleMiddleName,
                'tableComment' => $tableComment,
            ])
        );

        file_put_contents(
            $bundlePath . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $middleName . DIRECTORY_SEPARATOR . 'index.twig',
            $this->renderPHP((__DIR__ . '/tpl/views/index.twig'), [
                'bundleName' => $bundleName,
                'entityName' => $entityName,
                'middleName' => $middleName,
                'attributes' => $attributes,
                'attributesNoId' => $attributesNoId,
                'bundleMiddleName' => $bundleMiddleName,
                'tableComment' => $tableComment,
            ])
        );

        file_put_contents(
            $bundlePath . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $middleName . DIRECTORY_SEPARATOR . 'update.twig',
            $this->renderPHP((__DIR__ . '/tpl/views/update.twig'), [
                'bundleName' => $bundleName,
                'entityName' => $entityName,
                'middleName' => $middleName,
                'attributes' => $attributes,
                'bundleMiddleName' => $bundleMiddleName,
                'tableComment' => $tableComment,
            ])
        );

        file_put_contents(
            $bundlePath . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $middleName . DIRECTORY_SEPARATOR . 'view.twig',
            $this->renderPHP((__DIR__ . '/tpl/views/view.twig'), [
                'bundleName' => $bundleName,
                'entityName' => $entityName,
                'middleName' => $middleName,
                'attributes' => $attributes,
                'bundleMiddleName' => $bundleMiddleName,
                'tableComment' => $tableComment,
            ])
        );

        $output->writeln('');
        $output->writeln('Please add route:');
        $output->writeln("Route::annotation('" . $bundleName . "\\Controller\\" . $entityName . "Controller');");
        $output->writeln('');
        $output->writeln('SUCCESS');
        $output->writeln('');

    }

    protected function getField($tableName)
    {
        //提取MySQL的Comment
        $fieldArr = DB::table('')->findAllBySql('show full fields from ' . $tableName);

        //["Type"]=>
        //  int(11)
        //  tinyint(3) unsigned
        //varchar(255)

        //  ["Key"]=> "PRI"


        $attribute = array();
        foreach ($fieldArr as $item) {
            if (empty($item['Comment'])) {
                $comment = self::convert($item['Field'], false);
            } else {
                $comment = $item['Comment'];
            }
            $attribute[$item['Field']] = addslashes($comment);
        }

        return $attribute;
    }

    protected function getFieldType($tableName)
    {
        $fieldArr = DB::table('')->findAllBySql('show full fields from {{%' . $tableName . '}}');
        $attribute = array();
        foreach ($fieldArr as $item) {
            $attribute[$item['Field']] = self::getColumnPhpType($item['Type']);
        }

        return $attribute;
    }

    protected function getColumnPhpType($column)
    {
        $typeMap = [
            'tinyint' => 'integer',
            'bit' => 'integer',
            'smallint' => 'integer',
            'mediumint' => 'integer',
            'int' => 'integer',
            'integer' => 'integer',
            'bigint' => 'integer',
            'float' => 'float',
            'double' => 'float',
            'real' => 'float',
            'decimal' => 'float',
            'numeric' => 'float',
            'tinytext' => 'string',
            'mediumtext' => 'string',
            'longtext' => 'string',
            'longblob' => 'string',
            'blob' => 'string',
            'text' => 'string',
            'varchar' => 'string',
            'string' => 'string',
            'char' => 'string',
            'datetime' => 'string',
            'year' => 'string',
            'date' => 'string',
            'time' => 'string',
            'timestamp' => 'int',
            'enum' => 'string',
        ];

        foreach ($typeMap as $key => $v) {
            if (preg_match('/' . $key . '/', $column)) {
                return $v;
            }
        }
        return 'string';

    }

    /**
     * 字符串命名风格转换
     *
     * @param string $name
     * @param bool|true $pascalToLower 默认true,为帕斯卡命名转为小写C语言风格
     * @return string
     */
    protected static function convert($name, $pascalToLower = true)
    {
        if ($pascalToLower) {
            return strtolower(trim(preg_replace('/[A-Z]/', '_\\0', $name), '_'));
        } else {
            $name = preg_replace_callback('/\_([a-z])/', function ($matches) {
                return strtoupper($matches[1]);
            }, $name);
            return ucfirst($name);
        }
    }

    /**
     * 帕斯卡命名转为中杠分隔单词
     *
     * @param $name
     * @param bool|false $reverse 是否反转
     * @return string
     */
    protected static function convertToMiddle($name, $reverse = false)
    {
        if ($reverse) {
            return preg_replace_callback('/\-([a-z])/', function ($matches) {
                return strtoupper($matches[1]);
            }, $name);
        } else {
            return strtolower(trim(preg_replace('/[A-Z]/', '-\\0', $name), '-'));
        }
    }

    protected function getTableComment($tableName, $defualtValue)
    {
        $config = Application::$app['db.config'];

        if (isset($config['dsn'])) {
            if (preg_match('/dbname=(.*)/', $config['dsn'], $arr)) {
                $database = $arr[1];
            } else {
                die('database error');
            }
        } else {
            $database = $config['database'];
        }

        //去掉占位符 例如 "{{%user}}" 得到 "user"
        $tableName = preg_replace_callback(
            '/(\\{\\{(%?[\w\-\.\$ ]+%?)\\}\\}|\\[\\[([\w\-\. ]+)\\]\\])/',
            function ($matches) use ($config) {
                if (isset($matches[3])) {
                    return $matches[3];
                } else {
                    return str_replace('%', $config['tablePrefix'], $matches[2]);
                }
            },
            $tableName
        );

        //SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE table_name = 'pre_point' AND table_schema = 'shop_dev'
        $sql = 'SELECT * FROM ';
        $sql .= 'INFORMATION_SCHEMA.TABLES WHERE ';
        $sql .= "table_name = '{$tableName}' AND table_schema = '{$database}'";

        $arr = DB::table('')->findOneBySql($sql);

        if (isset($arr['TABLE_COMMENT'])) {
            return $arr['TABLE_COMMENT'];
        }
        return $defualtValue;
    }

    protected static function renderPHP($____tpl____, $____data____ = [])
    {
        extract($____data____);
        ob_start();
        require $____tpl____;
        return ob_get_clean();
    }
}