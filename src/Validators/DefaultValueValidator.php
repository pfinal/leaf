<?php

namespace Leaf\Validators;

/**
 * 设置默认值
 */
class DefaultValueValidator extends BaseValidator
{
    /**
     * @var mixed 默认值 或 php回调函数
     */
    public $value;

    /**
     * @var boolean
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
