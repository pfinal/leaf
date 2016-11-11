<?php

namespace Leaf\Validators;

use \Exception;

/**
 * Validator is the base class for all validators.
 *
 */
class Validator
{
    /**
     * @var array list of built-in validators (name => class or configuration)
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

        'double' => 'Leaf\Validators\NumberValidator',
        'integer' => [
            'class' => 'Leaf\Validators\NumberValidator',
            'integerOnly' => true,
        ],
        'number' => 'Leaf\Validators\NumberValidator',

        'exist' => 'Leaf\Validators\ExistValidator',
        'unique' => 'Leaf\Validators\UniqueValidator',

        //'file' => 'Leaf\Validators\FileValidator',
        'image' => 'Leaf\Validators\ImageValidator',

    ];
    /**
     * @var array|string attributes to be validated by this validator. For multiple attributes,
     * please specify them as an array; for single attribute, you may use either a string or an array.
     */
    public $attributes = [];
    /**
     * @var string the user-defined error message. It may contain the following placeholders which
     * will be replaced accordingly by the validator:
     *
     * - `{attribute}`: the label of the attribute being validated
     * - `{value}`: the value of the attribute being validated
     *
     * Note that some validators may introduce other properties for error messages used when specific
     * validation conditions are not met. Please refer to individual class API documentation for details
     * about these properties. By convention, this property represents the primary error message
     * used when the most important validation condition is not met.
     */
    public $message;
    /**
     * @var array|string scenarios that the validator can be applied to. For multiple scenarios,
     * please specify them as an array; for single scenario, you may use either a string or an array.
     */
    public $on = [];
    /**
     * @var array|string scenarios that the validator should not be applied to. For multiple scenarios,
     * please specify them as an array; for single scenario, you may use either a string or an array.
     */
    public $except = [];
    /**
     * @var boolean whether this validation rule should be skipped if the attribute being validated
     * already has some validation error according to some previous rules. Defaults to true.
     */
    public $skipOnError = true;
    /**
     * @var boolean whether this validation rule should be skipped if the attribute value
     * is null or an empty string.
     */
    public $skipOnEmpty = true;

    /**
     * @var callable a PHP callable that replaces the default implementation of [[isEmpty()]].
     * If not set, [[isEmpty()]] will be used to check if a value is empty. The signature
     * of the callable should be `function ($value)` which returns a boolean indicating
     * whether the value is empty.
     */
    public $isEmpty;
    /**
     * @var callable a PHP callable whose return value determines whether this validator should be applied.
     * The signature of the callable should be `function ($model, $attribute)`, where `$model` and `$attribute`
     * refer to the model and the attribute currently being validated. The callable should return a boolean value.
     *
     * This property is mainly provided to support conditional validation on the server side.
     * If this property is not set, this validator will be always applied on the server side.
     *
     * The following example will enable the validator only when the country currently selected is USA:
     *
     * ```php
     * function ($model) {
     *     return $model->country == Country::USA;
     * }
     * ```
     *
     * @see whenClient
     */
    public $when;

    public static function runValidate($rules, &$data, $labels)
    {

        $allow = array(
            'string', 'email', 'match', 'date', 'url', // string
            'number', 'integer', 'double', // number
            'compare',
            'boolean',// boolean
            'in',// in
            'safe', // safe
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
                            $errors[$attribute] = [$attribute . ' is not exist.'];
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
     * Creates a validator object.
     * @return Validator the validator
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
     * Validates a value.
     * A validator class can implement this method to support data validation out of the context of a data model.
     * @param mixed $value the data value to be validated.
     * @return array|null the error message and the parameters to be inserted into the error message.
     * Null should be returned if the data is valid.
     * @throws Exception if the validator does not supporting data validation without a model
     */
    protected function validateValue(&$value)
    {
        throw new Exception(get_class($this) . ' does not support validateValue().');
    }


    /**
     * Checks if the given value is empty.
     * A value is considered empty if it is null, an empty array, or the trimmed result is an empty string.
     * Note that this method is different from PHP empty(). It will return false when the value is 0.
     * @param mixed $value the value to be checked
     * @return boolean whether the value is empty
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
