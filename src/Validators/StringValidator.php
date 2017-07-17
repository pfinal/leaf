<?php

namespace Leaf\Validators;

use Leaf\Application;

/**
 * 验证字符串长度
 *
 * 这个验证器只能使用字符串类型的值
 */
class StringValidator extends BaseValidator
{
    /**
     * @var integer|array specifies the length limit of the value to be validated.
     * This can be specified in one of the following forms:
     *
     * - an integer: the exact length that the value should be of;
     * - an array of one element: the minimum length that the value should be of. For example, `[8]`.
     *   This will overwrite [[min]].
     * - an array of two elements: the minimum and maximum lengths that the value should be of.
     *   For example, `[8, 128]`. This will overwrite both [[min]] and [[max]].
     */
    public $length;

    /**
     * @var integer maximum length. If not set, it means no maximum length limit.
     */
    public $max;

    /**
     * @var integer minimum length. If not set, it means no minimum length limit.
     */
    public $min;

    /**
     * @var string user-defined error message used when the value is not a string
     */
    public $message;

    /**
     * @var string user-defined error message used when the length of the value is smaller than [[min]].
     */
    public $tooShort;

    /**
     * @var string user-defined error message used when the length of the value is greater than [[max]].
     */
    public $tooLong;

    /**
     * @var string user-defined error message used when the length of the value is not equal to [[length]].
     */
    public $notEqual;

    /**
     * @var string the encoding of the string value to be validated (e.g. 'UTF-8').
     */
    public $encoding;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        if (is_array($this->length)) {
            if (isset($this->length[0])) {
                $this->min = $this->length[0];
            }
            if (isset($this->length[1])) {
                $this->max = $this->length[1];
            }
            $this->length = null;
        }
        if ($this->encoding === null) {
            $this->encoding = Application::$app['charset'];// 'UTF-8'
        }
        if ($this->message === null) {
            $this->message = '{attribute} 必须是一个字符串';
        }
        if ($this->min !== null && $this->tooShort === null) {
            //$this->tooShort = '{attribute} should contain at least {min} length.';
            $this->tooShort = '{attribute}最少需要{min}个字符长度';
        }
        if ($this->max !== null && $this->tooLong === null) {
            //$this->tooLong = '{attribute} should contain at most {max} length.';
            $this->tooLong = '{attribute}最长不能超过{max}个字符长度';
        }
        if ($this->length !== null && $this->notEqual === null) {
            //$this->notEqual = '{attribute} should contain {length} length.';
            $this->notEqual = '{attribute}要求{length}个字符长度';
        }
    }

    /**
     * @inheritdoc
     */
    protected function validateValue(&$value)
    {
        if (!is_string($value)) {
            return [$this->message, []];
        }

        $length = mb_strlen($value, $this->encoding);

        if ($this->min !== null && $length < $this->min) {
            return [$this->tooShort, ['min' => $this->min]];
        }
        if ($this->max !== null && $length > $this->max) {
            return [$this->tooLong, ['max' => $this->max]];
        }
        if ($this->length !== null && $length !== $this->length) {
            return [$this->notEqual, ['length' => $this->length]];
        }

        return null;
    }
}
