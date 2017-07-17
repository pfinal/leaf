<?php

namespace Leaf\Validators;

use Exception;

/**
 * 将指定值与另一个值进行比较.
 */
class CompareValidator extends BaseValidator
{
    /**
     * @var string
     */
    public $compareAttribute;

    /**
     * @var mixed
     */
    public $compareValue;

    /**
     * @var string 要比较的值的类型。支持以下类型:
     *
     *  string: 将这些值作为字符串进行比较。在进行比较之前不会进行转换。
     *  number: 将这些值作为数字进行比较。在比较之前, 字符串值将转换为数字。
     */
    public $type = 'string';

    /**
     * @var string 用于比较的运算符。支持以下运算符:
     *
     * `==`: check if two values are equal. The comparison is done is non-strict mode.
     * `===`: check if two values are equal. The comparison is done is strict mode.
     * `!=`: check if two values are NOT equal. The comparison is done is non-strict mode.
     * `!==`: check if two values are NOT equal. The comparison is done is strict mode.
     * `>`: check if value being validated is greater than the value being compared with.
     * `>=`: check if value being validated is greater than or equal to the value being compared with.
     * `<`: check if value being validated is less than the value being compared with.
     * `<=`: check if value being validated is less than or equal to the value being compared with.
     */
    public $operator = '==';

    /**
     * @var string 用户定义的错误消息。它可能包含以下占位符 将由验证器相应地替换:
     *
     * `{attribute}`
     * `{value}`
     * `{compareValue}`
     * `{compareAttribute}`
     */
    public $message;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        if ($this->message === null) {
            switch ($this->operator) {
                case '==':
                    $this->message = '{attribute} must be repeated exactly.';
                    break;
                case '===':
                    $this->message = '{attribute} must be repeated exactly.';
                    break;
                case '!=':
                    $this->message = '{attribute} must not be equal to "{compareValue}".';
                    break;
                case '!==':
                    $this->message = '{attribute} must not be equal to "{compareValue}".';
                    break;
                case '>':
                    $this->message = '{attribute} must be greater than "{compareValue}".';
                    break;
                case '>=':
                    $this->message = '{attribute} must be greater than or equal to "{compareValue}".';
                    break;
                case '<':
                    $this->message = '{attribute} must be less than "{compareValue}".';
                    break;
                case '<=':
                    $this->message = '{attribute} must be less than or equal to "{compareValue}".';
                    break;
                default:
                    throw new Exception("Unknown operator: {$this->operator}");
            }
        }
    }

    /**
     * @inheritdoc
     */
    protected function validateValue(&$value)
    {
        if ($this->compareValue === null) {
            throw new Exception('CompareValidator::compareValue must be set.');
        }
        if (!$this->compareValues($this->operator, $this->type, $value, $this->compareValue)) {
            return [$this->message, [
                'compareAttribute' => $this->compareValue,
                'compareValue' => $this->compareValue,
            ]];
        } else {
            return null;
        }
    }

    /**
     * 将两个值与指定的运算符进行比较
     *
     * @param string $operator the comparison operator
     * @param string $type the type of the values being compared
     * @param mixed $value the value being compared
     * @param mixed $compareValue another value being compared
     * @return boolean whether the comparison using the specified operator is true.
     */
    protected function compareValues($operator, $type, $value, $compareValue)
    {
        if ($type === 'number') {
            $value = floatval($value);
            $compareValue = floatval($compareValue);
        } else {
            $value = (string)$value;
            $compareValue = (string)$compareValue;
        }
        switch ($operator) {
            case '==':
                return $value == $compareValue;
            case '===':
                return $value === $compareValue;
            case '!=':
                return $value != $compareValue;
            case '!==':
                return $value !== $compareValue;
            case '>':
                return $value > $compareValue;
            case '>=':
                return $value >= $compareValue;
            case '<':
                return $value < $compareValue;
            case '<=':
                return $value <= $compareValue;
            default:
                return false;
        }
    }
}
