<?php

namespace Leaf\Validators;

use Leaf\DB;

/**
 * 唯一验证器
 *
 * 示例 ['username', 'unique', 'table' => 'users', 'filter' => ['id != ?', [$id]]]
 *
 */
class UniqueValidator extends BaseValidator
{
    public $table;
    public $field;

    /**
     * 附加where条件
     * @var string|array 与DB的where方法参数对应 例如 ['id != ?', [$id]]
     */
    public $filter;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        if ($this->message === null) {
            $this->message = '{attribute} "{value}" 已存在';
        }
    }

    /**
     * @inheritdoc
     */
    protected function validateValue(&$value)
    {
        if ($this->table === null) {
            throw new \Exception('The "targetClass" property must be set.');
        }
        if (!is_string($this->field)) {
            throw new \Exception('The "field" property must be configured as a string.');
        }

        if (!preg_match('/^[\w\.]+$/', $this->field)) {
            throw new \Exception('The "field" 只能包含字母、数字、下划线或点');
        }

        $query = DB::table($this->table);

        if (is_array($this->filter)) {
            if (count($this->filter) === 2) {
                $query->where($this->filter[0], $this->filter[1]);
            } else {
                throw new \Exception('The "filter" 错误');
            }
        } else {
            $query->where($this->filter);
        }

        $query = $query->where("[[$this->field]]=?", [$value]);
        return $query->count() == 0 ? null : [$this->message, ['value' => $value]];
    }
}
