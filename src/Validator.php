<?php

namespace Leaf;

use Leaf\Validators\BaseValidator;

/**
 * 数据验证帮助类
 * @author  Zou Yiliang
 */
class Validator
{
    public static $errors = [];

    /**
     * 验证
     * @param $data
     * @param array $rules
     * @param array $labels
     * @param bool $inputOnly 只验证输入的数据(不验证缺少的字段)
     * @return bool
     */
    public static function validate(&$data, array $rules, array $labels = array(), $inputOnly = false)
    {
        $temp = [];
        foreach ($rules as $k => $v) {
            if ($v instanceof ValidateRuleMaker) {
                $temp = array_merge($temp, $v->getRules());
            } else {
                $temp[] = $v;
            }
        }

        self::$errors = BaseValidator::runValidate($temp, $data, $labels, $inputOnly);
        return count(self::$errors) === 0;
    }

    /**
     * @param $data
     * @param array $rules
     * @param array $labels
     * @return bool
     */
    public static function validateOnly(&$data, array $rules, array $labels = array())
    {
        return self::validate($data, $rules, $labels, true);
    }

    /**
     * 返回一条验证错误消息
     * @return string
     */
    public static function getFirstError()
    {
        $errors = array_values(static::getFirstErrors());
        return current($errors);
    }

    /**
     * 返回验证错误消息，每个字段只返回第一条消息
     * [
     *     'username' =>  'Username is required.',
     *     'email' => 'Email address is invalid.',
     * ]
     * @return array
     */
    public static function getFirstErrors()
    {
        if (empty(self::$errors)) {
            return [];
        } else {
            $errors = [];
            foreach (self::$errors as $name => $es) {
                if (!empty($es)) {
                    $errors[$name] = reset($es);
                }
            }

            return $errors;
        }
    }

    /**
     * 返回所有验证错误消息
     * [
     *     'username' => [
     *         'Username is required.',
     *         'Username must contain only word characters.',
     *     ],
     *     'email' => [
     *         'Email address is invalid.',
     *     ]
     * ]
     * @return array
     */
    public static function getErrors()
    {
        return self::$errors;
    }

    /**
     * @param string|array $field
     * @return ValidateRuleMaker
     */
    public static function makeRule($field)
    {
        return new ValidateRuleMaker($field);
    }

}

class ValidateRuleMaker
{
    private $field;
    private $rules = [];

    public function __construct($field)
    {
        $this->field = $field;
    }

    public function trim()
    {
        $this->rules[] = [$this->field, 'trim'];
        return $this;
    }

    public function required()
    {
        $this->rules[] = [$this->field, 'required'];
        return $this;
    }

    public function length($value)
    {
        $this->rules[] = [$this->field, 'string', 'length' => $value];
        return $this;
    }

    public function email(array $rules = [])
    {
        $this->rules[] = array_merge([$this->field, 'email'], $rules);
        return $this;
    }

    public function defaultValue($value)
    {
        $this->rules[] = [$this->field, 'default', 'value' => $value];
        return $this;
    }

    public function boolean()
    {
        $this->rules[] = [$this->field, 'boolean'];
        return $this;
    }

    public function safe()
    {
        $this->rules[] = [$this->field, 'safe'];
        return $this;
    }

    public function integer()
    {
        $this->rules[] = [$this->field, 'integer'];
        return $this;
    }

    public function in($range)
    {
        $this->rules[] = [$this->field, 'in', 'range' => $range];
        return $this;
    }

    public function filter($filter)
    {
        $this->rules[] = [$this->field, 'filter', 'filter' => $filter];
        return $this;
    }

    public function compare($compareValue)
    {
        $this->rules[] = [$this->field, 'compare', 'compareValue' => $compareValue];
        return $this;
    }

    public function date($format)
    {
        $this->rules[] = [$this->field, 'date', 'format' => $format];
        return $this;
    }

    public function url()
    {
        $this->rules[] = [$this->field, 'url'];
        return $this;
    }

    /**
     * @return array
     */
    public function getRules()
    {
        return $this->rules;
    }
}