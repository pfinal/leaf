<?php

namespace Leaf\Validators;

/**
 * 设置默认值
 *
 * DefaultValueValidator is not really a validator. It is provided mainly to allow
 * specifying attribute default values when they are empty.
 */
class DefaultValueValidator extends Validator
{
    /**
     * @var mixed the default value or a PHP callable that returns the default value which will
     * be assigned to the attributes being validated if they are empty. The signature of the PHP callable
     * should be as follows,
     *
     */
    public $value;
    /**
     * @var boolean this property is overwritten to be false so that this validator will
     * be applied when the value being validated is empty.
     */
    public $skipOnEmpty = false;


    /**
     * @inheritdoc
     */
    public function validateValue(&$value)
    {
        if ($this->isEmpty($value)) {
            if ($this->value instanceof \Closure) {
                $value = call_user_func($this->value, $value);
            } else {
                $value = $this->value;
            }
        }
    }
}
