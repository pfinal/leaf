<?php

namespace Leaf\Facade;

use Leaf\DB;
use PFinal\Database\Builder;
use Exception;

class EMFacade
{
    public static function __callStatic($name, $arguments)
    {
        $em = EntityManager::getInstance();

        if (method_exists($em, $name)) {
            return call_user_func_array([$em, $name], $arguments);
        }

        throw new \Exception('Call to undefined method ' . __CLASS__ . '::' . $name . '()');
    }
}

class EntityManager
{
    /**
     * @var \SplObjectStorage
     */
    protected $modelStorage;

    /**
     * @var static
     */
    protected static $instance;

    protected function __construct()
    {
        $this->modelStorage = new \SplObjectStorage();
    }

    /**
     * @return static
     */
    public static function getInstance()
    {
        if (static::$instance == null) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    protected function getTableName($class)
    {
        if (method_exists($class, 'tableName')) {
            return call_user_func($class . '::tableName');
        }

        //去掉namespace
        $name = rtrim(str_replace('\\', '/', $class), '/\\');
        if (($pos = mb_strrpos($name, '/')) !== false) {
            $name = mb_substr($name, $pos + 1);
        }

        //大写转为下划线风格
        return trim(strtolower(preg_replace('/[A-Z]/', '_\0', $name)), '_');
    }

    /**
     * @param $class
     * @return Builder
     */
    public function entity($class)
    {
        return DB::table($this->getTableName($class))->asEntity($class)->afterFind(function ($model) {
            if (is_object($model)) {
                $this->modelStorage->attach($model, serialize((array)$model));
            }
        });
    }

    /**
     * @param $entity
     * @param string $on create|update
     * @return array
     */
    private function getTimestamp($entity, $on)
    {
        if (!method_exists($entity, 'timestamp')) {
            return [];
        }

        $timestamp = $entity->timestamp();
        if (!array_key_exists($on, $timestamp)) {
            return [];
        }

        $val = [];
        //['created_at', 'updated_at'=>function(){return time();}]
        foreach ((array)$timestamp[$on] as $key => $item) {
            if (is_numeric($key)) {
                $val[$item] = date('Y-m-d H:i:s');
            } else {
                $val[$key] = call_user_func($item);
            }
        }

        return $val;
    }

    /**
     * @return bool
     */
    public function save($entity)
    {
        $tableName = $this->getTableName(get_class($entity));

        if ($this->isNewRecord($entity)) {

            $data = (array)$entity;
            $data = array_merge($data, $this->getTimestamp($entity, 'create'));

            if (($id = DB::table($tableName)->insertGetId($data)) > 0) {

                $field = $this->getAutoIncrementField($tableName);
                if ($field != null) {
                    $entity->$field = $id;
                }
                return true;
            }
            return false;

        } else {

            $original = unserialize($this->modelStorage->offsetGet($entity));

            // 提取修改部份数据
            $data = array_diff((array)$entity, $original);
            $data = array_merge($data, $this->getTimestamp($entity, 'update'));

            if (count($data) == 0) {
                return false;
            }

            return 1 == DB::table($tableName)->where($this->getPkWhere($tableName, $original))->update($data);
        }
    }

    /**
     * @param object $entity
     * @return bool
     */
    public function remove($entity)
    {
        $tableName = $this->getTableName(get_class($entity));
        $original = unserialize($this->modelStorage->offsetGet($entity));
        return 1 == DB::table($this->getTableName(get_class($entity)))->where($this->getPkWhere($tableName, $original))->delete();
    }

    public function isNewRecord($entity)
    {
        return !$this->modelStorage->contains($entity);
    }

    private function getPkWhere($tableName, array $attributes)
    {
        $return = array();
        foreach ($this->queryPrimaryKeyFields($tableName) as $field) {
            if (array_key_exists($field, $attributes)) {
                $return[$field] = $attributes[$field];
            }
        }

        return $return;
    }

    /**
     * 查询主键字段
     * @return array
     * @throws Exception
     */
    private function queryPrimaryKeyFields($tableName)
    {
        $fields = static::schema($tableName);

        $primary = [];
        foreach ($fields as $field) {
            if ($field['Key'] === 'PRI') {
                $primary[] = $field['Field'];
            }
        }

        if (count($primary) == 0) {
            throw new Exception('没有主键字段');
        }

        return $primary;
    }

    /**
     * 查询自增字段
     * @return string | null
     */
    private function getAutoIncrementField($tableName)
    {
        foreach (static::schema($tableName) as $field) {
            if (stripos($field['Extra'], 'auto_increment') !== false) {
                return (string)$field['Field'];
            }
        }
    }

    private static $schemas = array();

    /**
     * @param string $tableName
     * @return array
     */
    private function schema($tableName)
    {
        if (!array_key_exists($tableName, static::$schemas)) {
            static::$schemas[$tableName] = DB::getConnection()->query('SHOW FULL FIELDS FROM ' . DB::addPrefix($tableName));
        }
        return static::$schemas[$tableName];
    }

    /**
     * 加载数据库字段默认值
     * @param object $entity
     * @return object
     */
    public function loadDefaultValues($entity = null)
    {
        $fields = DB::getConnection()->query('SHOW FULL FIELDS FROM ' . DB::addPrefix($this->getTableName(get_class($entity))));
        $defaults = array_column($fields, 'Default', 'Field');

        foreach ($defaults as $key => $value) {
            $entity->$key = $value;
        }
        return $entity;
    }
}
