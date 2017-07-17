<?php

namespace Leaf\Validators;

/**
 * 验证是否为boolean值
 */
class BooleanValidator extends BaseValidator
{
    /**
     * @var mixed
     */
    public $trueValue = '1';

    /**
     * @var mixed
     */
    public $falseValue = '0';

    /**
     * @var boolean
     */
    public $strict = false;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        if ($this->message === null) {
            //$this->message = '{attribute} must be either "{true}" or "{false}".';
            $this->message = '{attribute}只能是"{true}"或"{false}"';
        }
    }

    /**
     * @inheritdoc
     */
    protected function validateValue(&$value)
    {
        $valid = !$this->strict && ($value == $this->trueValue || $value == $this->falseValue)
            || $this->strict && ($value === $this->trueValue || $value === $this->falseValue);
        if (!$valid) {
            return [$this->message, [
                'true' => $this->trueValue,
                'false' => $this->falseValue,
            ]];
        } else {
            return null;
        }
    }

}
