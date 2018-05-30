<?php

namespace Leaf\Auth;

use Leaf\Application;
use Leaf\BaseObject;

class User extends BaseObject implements Authenticatable
{
    /**
     * 返回数据库的表名
     *
     * @return string
     */
    public static function tableName()
    {
        return '{{%user}}';
    }

    /**
     * 返回用户ID
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 权限检查
     *
     * @param string $ability 权限名称
     * @return bool
     */
    public function can($ability)
    {
        //return call_user_func_array(array(Application::$app['gate'], 'check'), func_get_args());
        return call_user_func_array(array(Application::$app['gate']->forUser($this), 'check'), func_get_args());
    }
}