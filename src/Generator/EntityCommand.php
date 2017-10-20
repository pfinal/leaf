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

class EntityCommand extends Command
{
    protected $name = 'make:entity';
    protected $description = 'create a new entity';

    protected function configure()
    {
        $this->setName($this->name)
            ->setDescription($this->description);
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->write("TableName:");
        $tableName = trim(fgets(STDIN));

        $entityName = $this->convert($tableName, false);

        $attributes = $allAttributes = self::getField($tableName);
        unset($attributes['created_at']);
        unset($attributes['updated_at']);

        $attributesNoId = $attributes;
        unset($attributesNoId['id']);

        $rootPath = Application::$app['path'] . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;

        $checkList = [
            $rootPath . 'Entity' . DIRECTORY_SEPARATOR . $entityName . '.php',
        ];

        foreach ($checkList as $file) {
            if (file_exists($file)) {
                $output->writeln($file . ' exist.');
                return;
            }
        }

        file_put_contents(
            $rootPath . 'Entity' . DIRECTORY_SEPARATOR . $entityName . '.php',
            View::renderText(file_get_contents(__DIR__ . '/tpl/entity.twig'), [
                'entityName' => $entityName,
                'tableName' => $tableName,
                'attribute' => $attributesNoId,
                'fieldType' => self::getFieldType($tableName),
                'tableComment' => self::getTableComment($tableName),
            ])
        );

        $output->writeln('SUCCESS');

    }

    protected function getTableComment($tableName)
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

        //SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE table_name = 'pre_point' AND table_schema = 'shop_dev'
        $sql = 'SELECT * FROM ';
        $sql .= 'INFORMATION_SCHEMA.TABLES WHERE ';
        $sql .= "table_name = '{$config['tablePrefix']}{$tableName}' AND table_schema = '{$database}'";

        $arr = DB::table($tableName)->findOneBySql($sql);

        if (isset($arr['TABLE_COMMENT']) && !empty($arr['TABLE_COMMENT'])) {
            return $arr['TABLE_COMMENT'];
        }
        return self::convert($tableName, false);
    }


    protected function getField($tableName)
    {
        //提取MySQL的Comment
        $fieldArr = DB::table('')->findAllBySql('show full fields from {{%' . $tableName . '}}');

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

    protected static function renderPHP($____tpl____, $____data____ = [])
    {
        extract($____data____);
        ob_start();
        require $____tpl____;
        return ob_get_clean();
    }
}
