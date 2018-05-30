<?php

namespace Leaf;

use Exception;

/**
 * Object类是实现了动态调用属性功能和数组方式访问的基类
 * @author  Zou Yiliang
 */
class BaseObject implements \ArrayAccess, \JsonSerializable
{
    use ArrayAccessTrait;

    public static function className()
    {
        return get_called_class();
    }

    /**
     * 支持用属性的方式，访问get开头的方法
     *
     * 例如 $obj->getName() 可以用 $obj->name 的方式来调用
     *
     * @param $name
     * @return mixed
     * @throws Exception
     */
    public function __get($name)
    {
        $getter = 'get' . $name;
        if (method_exists($this, $getter)) {
            return $this->$getter();
        }
        throw new Exception('Undefined property: ' . get_class($this) . '::' . $name);
    }

    public function __set($name, $value)
    {
        $setter = 'set' . $name;
        if (method_exists($this, $setter)) {
            $this->$setter($value);
            return;
        }
        $this->$name = $value;
    }

    /**
     * 实现JsonSerializable接口，方便转为json时自定义数据
     * @return array
     */
    public function jsonSerialize()
    {
        $arr = array();

        //过滤掉protected和private的属性
        //protected的属性，转为数组后，key开头字符为 \u0000*\u0000
        //private的属性，转为数组后，key开头字符为： \u0000ClassName\u0000  "ClassName"为实际完整类名
        foreach ((array)$this as $k => $v) {
            if (substr($k, 0, 1) !== chr(0)) {
                $arr[$k] = $v;
            }
        }

        return $arr;
    }
}