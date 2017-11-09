<?php

namespace Leaf\Validators;

/**
 * 行内验证器是一种以方法或匿名函数的形式定义的验证器
 */
class InlineValidator extends BaseValidator
{
    /**
     * @var \Closure 匿名函数
     *
     * 验证未通过时, 返回原因(string), 验证通过返回 null
     * 返回的字符串中，支持占位符 {attribute}
     *
     * ```php
     * function foo($attribute)
     * ```
     */
    public $method;

    /**
     * @inheritdoc
     */
    public function validateValue(&$value)
    {
        $method = $this->method;
        $result = call_user_func($method, $value);
        if ($result === null) {
            return null;
        }

        return [(string)$result, []];
    }
}
