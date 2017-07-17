<?php
namespace Leaf\Validators;

/**
 * 验证是否在指定范围中
 *
 * The range can be specified via the [[range]] property.
 * If the [[not]] property is set true, the validator will ensure the attribute value
 * is NOT among the specified range.
 *
 */
class RangeValidator extends BaseValidator
{
    /**
     * @var array list of valid values that the attribute value should be among
     */
    public $range;

    /**
     * @var boolean whether the comparison is strict (both type and value must be the same)
     */
    public $strict = false;

    /**
     * @var boolean whether to invert the validation logic. Defaults to false. If set to true,
     * the attribute value should NOT be among the list of values defined via [[range]].
     */
    public $not = false;

    /**
     * @var boolean whether to allow array type attribute.
     */
    public $allowArray = false;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        if (!is_array($this->range)) {
            throw new \Exception('The "range" property must be set.');
        }
        if ($this->message === null) {
            //$this->message = '{attribute} is invalid.';
            $this->message = '{attribute}无效';
        }
    }

    /**
     * @inheritdoc
     */
    protected function validateValue(&$value)
    {
        if (!$this->allowArray && is_array($value)) {
            return [$this->message, []];
        }

        $in = true;

        foreach ((is_array($value) ? $value : [$value]) as $v) {
            if (!in_array($v, $this->range, $this->strict)) {
                $in = false;
                break;
            }
        }

        return $this->not !== $in ? null : [$this->message, []];
    }
}
