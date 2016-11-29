<?php

namespace Leaf\Auth;

use Leaf\DB;
use Leaf\Session;

/**
 * 用户认证
 * Class AuthManager
 * @package Leaf\Auth
 */
class AuthManager
{
    private static function getSessionKey()
    {
        return md5(get_called_class()) . '.user.id';
    }

    /**
     * 将用户置为登录状态
     * @param User $user
     * @return bool
     */
    public static function login(User $user)
    {
        Session::set(static::getSessionKey(), $user->getId());
        return true;
    }

    /**
     * 将指定id的用户置为登录状态
     * @param $id
     * @return bool
     */
    public static function loginUsingId($id)
    {
        Session::set(static::getSessionKey(), $id);
        return true;
    }

    /**
     * 退出登录状态
     * @return bool
     */
    public static function logout()
    {
        Session::remove(static::getSessionKey());
        return true;
    }

    /**
     * 用户未登录状态返回true
     * @return bool
     */
    public static function isGuest()
    {
        return Session::get(static::getSessionKey()) == null;
    }

    /**
     * 检查用户是否已登录，已登录时返回true
     * @return bool
     */
    public static function check()
    {
        return !static::isGuest();
    }

    public static function getId()
    {
        return Session::get(static::getSessionKey());
    }

    /**
     * 返回当前登录用户
     * @return User
     */
    public static function getUser()
    {
        return static::retrieveById(static::getId());
    }

    /**
     * 通过id取回用户
     *
     * @param  int $id
     * @return User
     */
    protected static function retrieveById($id)
    {
        return DB::table(User::tableName())->asEntity(User::className())->findByPk($id);
    }
}