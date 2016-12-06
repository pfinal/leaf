<?php

namespace Leaf\Auth;

use Leaf\Application;
use Leaf\Object;

class User extends Object
{
    /**
     * 返回数据库中的表名
     * @return string
     */
    public static function tableName()
    {
        return 'user';
    }

    /**
     * 返回用户ID
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 权限检查
     * @param string $ability 权限名称
     * @return bool
     */
    public function can($ability)
    {
        return call_user_func_array(array(Application::$app['gate'], 'check'), func_get_args());
    }

    /**
     * 生成 password hash
     * @param string $password
     * @param string $salt
     * @return string
     */
    public static function makePasswordHash($password, $salt = '')
    {
        return md5($password . $salt);
    }
}