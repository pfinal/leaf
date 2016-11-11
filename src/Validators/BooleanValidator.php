<?php

namespace Leaf\Validators;

/**
 * 验证是否为boolean值
 *
 * Possible boolean values can be configured via the [[trueValue]] and [[falseValue]] properties.
 * And the comparison can be either [[strict]] or not.
 *
 */
class BooleanValidator extends Validator
{
    /**
     * @var mixed the value representing true status. Defaults to '1'.
     */
    public $trueValue = '1';
    /**
     * @var mixed the value representing false status. Defaults to '0'.
     */
    public $falseValue = '0';
    /**
     * @var boolean whether the comparison to [[trueValue]] and [[falseValue]] is strict.
     * When this is true, the attribute value and type must both match those of [[trueValue]] or [[falseValue]].
     * Defaults to false, meaning only the value needs to be matched.
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
