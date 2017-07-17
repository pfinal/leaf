<?php

namespace Leaf\Validators;

use Leaf\DB;

/**
 * 检查输入值是否在某表字段中存在
 *
 * 例如
 * CREATE TABLE `pre_config` (
 *       `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
 *       `name` varchar(50) NOT NULL DEFAULT '',
 *       `value` varchar(50) NOT NULL DEFAULT '',
 *       PRIMARY KEY (`id`)
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
 *
 * INSERT INTO `pre_config` (`id`, `name`, `value`) VALUES (1, 'grade', 'A'), (2, 'grade', 'B');
 *
 * ['grade', 'exist', 'table' => 'config', 'field' => 'value', 'filter' => ['name=?', ['grade']]],
 *
 * 将生成如下sql查询
 * SELECT COUNT(*) FROM `pre_config` WHERE (`name`='grade') AND (`value`='A')
 */
class ExistValidator extends BaseValidator
{
    public $table;
    public $field;

    /**
     * 附加where条件
     * @var string|array 与DB的where方法参数对应 例如 ['name=?', ['grade']]
     */
    public $filter;
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
        if ($this->message === null) {
            $this->message = '{attribute}无效';//{attribute} is invalid.
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

        if (is_array($value)) {

            if (!$this->allowArray) {
                return [$this->message, []];
            }

            $query = $query->whereIn($this->field, $value);
            return $query->count("DISTINCT [[$this->field]]") == count($value) ? null : [$this->message, []];
        } else {

            $query = $query->where("[[$this->field]]=?", [$value]);
            return $query->count() > 0 ? null : [$this->message, []];
        }
    }
}
