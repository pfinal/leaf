<?php

namespace Leaf\Validators;

/**
 * 虚拟验证，其主要目的是标记安全的值
 */
class SafeValidator extends BaseValidator
{
    public $skipOnEmpty = false;

    protected function validateValue(&$value)
    {
    }
}
