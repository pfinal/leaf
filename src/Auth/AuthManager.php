<?php

namespace Leaf\Auth;

use Leaf\Exception\HttpException;
use Leaf\Request;
use Leaf\Session;
use Leaf\Util;

/**
 * 用户认证管理
 */
class AuthManager
{
    const LOGIN_REQUIRED = 'LOGIN_REQUIRED';

    /**
     * @var Authenticatable $user
     */
    protected static $user = null;

    private static function getSessionKey()
    {
        return md5(get_called_class()) . '.user.id';
    }

    /**
     * 将用户置为登录状态
     *
     * @param Authenticatable $user
     * @param bool $remember 是否记住
     * @return bool
     */
    public static function login(Authenticatable $user, $remember = false)
    {
        if ($remember) {
            static::setCookie($user->getId());
        }
        return static::_login($user);
    }

    private static function setCookie($usreId)
    {
        setcookie('remember_token', static::updateRememberToken($usreId), time() + 60 * 60 * 24 * 365, '/');
    }

    /**
     * 置登录
     * @param Authenticatable $user
     * @param bool $once 一次性登录
     * @param bool $fromCookie 是否来自cookie中的记住我
     * @return bool
     */
    private static function _login(Authenticatable $user, $once = false, $fromCookie = false)
    {
        if (!static::beforeLogin($user, $fromCookie)) {
            return false;
        }

        static::$user = $user;

        if (!$once) {
            Session::set(static::getSessionKey(), $user->getId());
        }

        static::afterLogin($fromCookie, $once);

        return true;
    }

    /**
     * 返回当前登录用户
     * 如果未登录状调用此方法，会得到500异常，应先调用check()方法检查是否已登录
     * @return Authenticatable
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
     *
     * @param $id
     * @param bool $remember 是否记住
     * @return bool
     */
    public static function loginUsingId($id, $remember = false)
    {
        $user = static::retrieveById($id);
        if ($user == null) {
            return false;
        }
        if ($remember) {
            static::setCookie($user->getId());
        }
        static::_login($user);
        return true;
    }

    /**
     * 一次性认证登录
     * @param $id
     * @return bool
     */
    public static function onceUsingId($id)
    {
        $user = static::retrieveById($id);
        if ($user == null) {
            return false;
        }
        return static::_login($user, true);
    }

    /**
     * 退出登录状态
     * @return bool
     */
    public static function logout()
    {
        if (static::isGuest()) {
            return true;
        }

        static::updateRememberToken(static::getUser()->getId());//不操作cookie，直接update token
        Session::remove(static::getSessionKey());
        static::$user = null;
        return true;
    }

    /**
     * 是否在未登录状态
     * @return bool 未登录状态返回true，已登录返回false
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

    /**
     * 返回当前用户id
     *
     * @return int
     */
    public static function getId()
    {
        return static::getUser()->getId();
    }

    /**
     * 尝试从Cookie记住我登录
     *
     * @param Request $request
     * @param int $day 记住我多少天内有效
     */
    public static function attemptFromRemember(Request $request, $day = 30)
    {
        if (!static::isGuest()) {
            return;
        }
        $token = $request->cookies->get('remember_token', '');

        if (strlen($token) <= 32) {
            return;
        }
        $time = substr($token, 32);
        if (time() - $time > 60 * 60 * 24 * $day) {
            return;
        }

        $user = static::retrieveByToken($token);

        if ($user != null) {
            static::_login($user, false, true);
        }
    }

    /**
     * 更新记住我功能的token值 (用户修改密码时，应调用此方法)
     *
     * @return string 返回更新后的token，如果更新失败，返回空字符串
     */
    public static function updateRememberToken($userId)
    {
        $token = str_replace('-', '', Util::guid()) . time(); // 32位字符串 + 当前时间戳
        return static::saveRememberToken($userId, $token) ? $token : '';
    }

    /**
     * 登录前置操作，此方法返回true时，用户才被允许登录
     *
     * @param Authenticatable $user
     * @param bool $fromRemember 是否来自记住我功能
     * @return bool
     */
    public static function beforeLogin($user, $fromRemember)
    {
        return true;
    }

    /**
     * 通过token取回用户
     *
     * @param string $token
     * @return Authenticatable|null
     */
    protected static function retrieveByToken($token)
    {
        return null;
    }

    /**
     * 保存token
     *
     * @param int $userId
     * @param string $token
     * @return bool
     */
    public static function saveRememberToken($userId, $token)
    {
        return false;
    }

    /**
     * 通过id取回用户
     *
     * @param int $id
     * @return Authenticatable|null
     * @throws \Exception
     */
    protected static function retrieveById($id)
    {
        throw new \Exception('Call to undefined method ' . get_called_class() . '::retrieveById()');
    }

    /**
     * 登录成功
     * @param bool $fromCookie
     * @param bool $once
     */
    protected static function afterLogin($fromCookie = false, $once = false)
    {
    }
}