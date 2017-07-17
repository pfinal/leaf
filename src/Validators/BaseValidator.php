<?php

namespace Leaf\Validators;

use Exception;

/**
 * 验证器基类
 */
class BaseValidator
{
    /**
     * @var array 验证器列表 (name => class or configuration)
     */
    public static $builtInValidators = [
        'required' => 'Leaf\Validators\RequiredValidator',
        'compare' => 'Leaf\Validators\CompareValidator',
        'default' => 'Leaf\Validators\DefaultValueValidator',
        'filter' => 'Leaf\Validators\FilterValidator',
        'trim' => [
            'class' => 'Leaf\Validators\FilterValidator',
            'filter' => 'trim',
            'skipOnArray' => true,
        ],
        'email' => 'Leaf\Validators\EmailValidator',
        'match' => 'Leaf\Validators\RegularExpressionValidator',
        'string' => 'Leaf\Validators\StringValidator',
        'safe' => 'Leaf\Validators\SafeValidator',
        'boolean' => 'Leaf\Validators\BooleanValidator',
        'in' => 'Leaf\Validators\RangeValidator',
        'date' => 'Leaf\Validators\DateValidator',
        'url' => 'Leaf\Validators\UrlValidator',
        'number' => 'Leaf\Validators\NumberValidator',
        'double' => 'Leaf\Validators\NumberValidator',
        'integer' => [
            'class' => 'Leaf\Validators\NumberValidator',
            'integerOnly' => true,
        ],
        'exist' => 'Leaf\Validators\ExistValidator',
        'unique' => 'Leaf\Validators\UniqueValidator',
        'image' => 'Leaf\Validators\ImageValidator',
    ];

    /**
     * @var array 需要验证的字段
     */
    public $attributes = [];

    /**
     * @var string 自定义错误消息
     *
     *  `{attribute}`: 当前正在验证的字段
     *  `{value}`: 当前验证字段对应的址
     *
     * 注意：某些验证器可能引入更多的的错误信息
     */
    public $message;

    /**
     * @var array
     */
    public $on = [];

    /**
     * @var array
     */
    public $except = [];

    /**
     * @var boolean
     */
    public $skipOnError = true;

    /**
     * @var boolean
     */
    public $skipOnEmpty = true;

    /**
     * @var callable 取代了 [[isEmpty ()]] 的默认实现。
     * 如果未设置, [[isEmpty()]] 将用于检查值是否为空。 它返回一个布尔值, 表示示值是否为空。
     */
    public $isEmpty;

    /**
     * @var callable
     */
    public $when;

    public static function runValidate($rules, &$data, $labels)
    {
        $allow = array(
            'string', 'email', 'match', 'date', 'url',
            'number', 'integer', 'double',
            'compare',
            'boolean',
            'in',
            'safe',
            'exist', 'unique',
            'image',
        );

        $validationFields = [];
        $errors = [];
        $obj = new self;
        foreach ($rules as $rule) {
            $validator = self::createValidator($rule[1], array_slice($rule, 2));

            if (!is_array($rule[0])) {
                $rule[0] = [$rule[0]];
            }

            foreach ($rule[0] as $attribute) {

                $allRuleFields[] = $attribute;

                if (!array_key_exists($attribute, $data)) {

                    if ($rule['1'] === 'default') {
                        $data[$attribute] = null;
                    } else {
                        if (!isset($errors[$attribute])) {
                            $errors[$attribute] = [$attribute . ' 未传入值'];
                        }
                        continue;
                    }
                }

                //已验证过的字段
                if (in_array($rule[1], $allow)) {
                    $validationFields[] = $attribute;
                }

                $skip = $validator->skipOnEmpty && $obj->isEmpty($data[$attribute]);
                if (!$skip) {
                    if (($error = $validator->validateValue($data[$attribute])) !== null) {
                        if (!isset($errors[$attribute])) {
                            $errors[$attribute] = [];
                        }

                        $p = array_merge($error[1]);

                        if (isset($labels[$attribute])) {
                            $p['attribute'] = $labels[$attribute];
                        } else {
                            $p['attribute'] = $attribute;
                        }


                        $params = [];
                        foreach ($p as $k => $v) {
                            $params['{' . $k . '}'] = $v;
                        }

                        $errors[$attribute][] = strtr($error[0], $params);
                    }

                }

            }
        }

        //检查是否全部值都有验证规则
        $validationFields = array_unique($validationFields);
        foreach ($data as $key => $v) {
            if (!in_array($key, $validationFields)) {
                if (!isset($errors[$key])) {
                    $errors[$key] = [];
                }

                if (isset($labels[$key])) {
                    $label = $labels[$key];
                } else {
                    $label = $key;
                }

                $errors[$key][] = $label . '缺少验证规则';
            }
        }

        return $errors;
    }

    /**
     * 创建验证器对象
     *
     * @return BaseValidator
     */
    public static function createValidator($type, $params = [])
    {
        if (isset(static::$builtInValidators[$type])) {
            $type = static::$builtInValidators[$type];
        }
        if (is_array($type)) {
            $params = array_merge($type, $params);
        } else {
            $params['class'] = $type;
        }
        return self::createObject($params);
    }

    public static function createObject($type)
    {
        $class = $type['class'];
        unset($type['class']);

        /** @var static $object */
        $object = new $class;
        foreach ($type as $name => $value) {
            $object->$name = $value;
        }
        $object->init();
        return $object;
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->attributes = (array)$this->attributes;
        $this->on = (array)$this->on;
        $this->except = (array)$this->except;
    }

    /**
     * 验证一个值
     *
     * @param $value
     * @return array|null
     * @throws Exception
     */
    protected function validateValue(&$value)
    {
        throw new Exception(get_class($this) . ' does not support validateValue().');
    }


    /**
     * 检查给定的值是否为空
     *
     * @return boolean
     */
    public function isEmpty($value)
    {
        if ($this->isEmpty !== null) {
            return call_user_func($this->isEmpty, $value);
        } else {
            return $value === null || $value === [] || $value === '';
        }
    }
}
