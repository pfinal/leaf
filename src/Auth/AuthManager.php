<?php

namespace Leaf\Auth;

use Leaf\DB;
use Leaf\Exception\HttpException;
use Leaf\Session;

/**
 * 用户认证
 * Class AuthManager
 */
class AuthManager
{
    /**
     * @var User $user
     */
    private static $user = null;

    const LOGIN_REQUIRED = 'LOGIN_REQUIRED';

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
        static::$user = $user;
        Session::set(static::getSessionKey(), $user->getId());
        return true;
    }

    /**
     * 返回当前登录用户
     * 如果未登录状调用此方法，会得到500异常，应先调用check()方法检查是否已登录
     * @return User
     */
    public static function getUser()
    {
        if (static::$user == null) {
            $id = Session::get(self::getSessionKey());
            if ($id == null || (static::$user = static::retrieveById($id)) == null) {
                throw new HttpException(500, static::LOGIN_REQUIRED);
            }
        }
        return static::$user;
    }

    /**
     * 将指定id的用户置为登录状态
     * @param $id
     * @return bool
     */
    public static function loginUsingId($id)
    {
        $user = static::retrieveById($id);
        if ($user == null) {
            return false;
        }
        static::login($user);
        return true;
    }

    /**
     * 一次性认证
     * @param $id
     * @return bool
     */
    public function onceUsingId($id)
    {
        $user = static::retrieveById($id);
        if ($user == null) {
            return false;
        }
        static::$user = $user;
        return true;
    }

    /**
     * 退出登录状态
     * @return bool
     */
    public static function logout()
    {
        static::$user = null;
        Session::remove(static::getSessionKey());
        return true;
    }

    /**
     * 用户未登录状态返回true
     * @return bool
     */
    public static function isGuest()
    {
        $user = null;
        try {
            $user = static::getUser();
        } catch (HttpException $ex) {
            if ($ex->getMessage() === static::LOGIN_REQUIRED) {
                return true;
            }
            throw $ex;
        }

        return $user == null;
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
        return static::getUser()->getId();
    }

    /**
     * 通过id取回用户
     *
     * @param  int $id
     * @return User|null
     */
    protected static function retrieveById($id)
    {
        return DB::table(User::tableName())->asEntity(User::className())->findByPk($id);
    }
}