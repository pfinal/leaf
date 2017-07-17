<?php

namespace Leaf\Validators;

/**
 * 验证是否为数字
 *
 * 该数字的格式必须与 [[integerPattern]] 或 [[[numberPattern]] 中指定的正则表达式匹配。
 * 或者, 可以配置 [[最大]] 和 [[最小]] 属性, 以确保数字在一定范围内。
 */
class NumberValidator extends BaseValidator
{
    /**
     * @var boolean whether the attribute value can only be an integer. Defaults to false.
     */
    public $integerOnly = false;

    /**
     * @var integer|float upper limit of the number. Defaults to null, meaning no upper limit.
     */
    public $max;

    /**
     * @var integer|float lower limit of the number. Defaults to null, meaning no lower limit.
     */
    public $min;

    /**
     * @var string user-defined error message used when the value is bigger than [[max]].
     */
    public $tooBig;

    /**
     * @var string user-defined error message used when the value is smaller than [[min]].
     */
    public $tooSmall;

    /**
     * @var string the regular expression for matching integers.
     */
    public $integerPattern = '/^\s*[+-]?\d+\s*$/';

    /**
     * @var string the regular expression for matching numbers. It defaults to a pattern
     * that matches floating numbers with optional exponential part (e.g. -1.23e-10).
     */
    public $numberPattern = '/^\s*[-+]?[0-9]*\.?[0-9]+([eE][-+]?[0-9]+)?\s*$/';

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        if ($this->message === null) {
            //$this->message = $this->integerOnly ? '{attribute} must be an integer.' : '{attribute} must be a number.';
            $this->message = $this->integerOnly ? '{attribute}要求为整数类型' : '{attribute}要求为数字类型';
        }
        if ($this->min !== null && $this->tooSmall === null) {
            //$this->tooSmall = '{attribute} must be no less than {min}.';
            $this->tooSmall = '{attribute}不能小于{min}';
        }
        if ($this->max !== null && $this->tooBig === null) {
            //$this->tooBig = '{attribute} must be no greater than {max}.';
            $this->tooBig = '{attribute}不能大于{max}';
        }
    }

    /**
     * @inheritdoc
     */
    protected function validateValue(&$value)
    {
        if (is_array($value)) {
            return ['{attribute} is invalid.', []];
        }
        $pattern = $this->integerOnly ? $this->integerPattern : $this->numberPattern;
        if (!preg_match($pattern, "$value")) {
            return [$this->message, []];
        } elseif ($this->min !== null && $value < $this->min) {
            return [$this->tooSmall, ['min' => $this->min]];
        } elseif ($this->max !== null && $value > $this->max) {
            return [$this->tooBig, ['max' => $this->max]];
        } else {
            return null;
        }
    }

}
