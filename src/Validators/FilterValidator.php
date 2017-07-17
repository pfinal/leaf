<?php

namespace Leaf\Validators;

/**
 * 根据筛选器转换属性值
 *
 * FilterValidator 实际上不是一个验证器, 而是一个数据处理器。
 * 它调用指定的回调来处理属性值并将处理后的值保存回属性。
 *
 * ~~~
 * function foo($value) {...return $newValue; }
 * ~~~
 *
 * 一些php函数也可以使用，例如`trim()`
 *
 * 若要指定筛选器, 请将 [[filter]] 属性设置为回调。
 *
 */
class FilterValidator extends BaseValidator
{
    /**
     * @var callable the filter. This can be a global function name, anonymous function, etc.
     * The function signature must be as follows,
     *
     * ~~~
     * function foo($value) {...return $newValue; }
     * ~~~
     */
    public $filter;

    /**
     * @var boolean whether the filter should be skipped if an array input is given.
     * If false and an array input is given, the filter will not be applied.
     */
    public $skipOnArray = false;

    /**
     * @var boolean this property is overwritten to be false so that this validator will
     * be applied when the value being validated is empty.
     */
    public $skipOnEmpty = false;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        if ($this->filter === null) {
            throw new \Exception('The "filter" property must be set.');
        }
    }

    /**
     * @inheritdoc
     */
    public function validateValue(&$value)
    {
        if (!$this->skipOnArray || !is_array($value)) {
            $value = call_user_func($this->filter, $value);
        }
    }
}
