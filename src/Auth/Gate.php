<?php

namespace Leaf\Auth;

/**
 * 用户授权
 * Class Gate
 * @package Leaf\Auth
 */
class Gate
{
    protected $userResolver;
    protected $abilities = array();

    /**
     * Gate constructor.
     * @param callable $userResolver 用户解析器，返回需要鉴权的User对象
     * @param array $abilities
     */
    public function __construct(callable $userResolver, array $abilities = array())
    {
        $this->userResolver = $userResolver;
        $this->abilities = $abilities;
    }

    /**
     * @param string $ability 权限
     * @param callable $callback 判断user是否拥有该能力，返回bool
     *
     *  使用示例:
     *  $gate->define('update-article', 'ArticlePolicy@update');
     *  $gate->define('update-article', function($user,$article){return true;});
     *
     * @return $this
     */
    public function define($ability, $callback)
    {
        if (is_callable($callback)) {
            $this->abilities[$ability] = $callback;
        } elseif (is_string($callback) && strpos($callback, '@') !== false) {
            $this->abilities[$ability] = $this->buildAbilityCallback($callback);
        } else {
            throw new \InvalidArgumentException("Callback must be a callable or a 'Class@method' string.");
        }

        return $this;
    }

    protected function buildAbilityCallback($callback)
    {
        return function () use ($callback) {
            list($class, $method) = explode('@', $callback);
            return call_user_func_array(array(new $class, $method), func_get_args());
        };
    }

    /**
     * 判断是否有权限
     * @param string $ability
     * @return bool
     */
    public function check($ability)
    {
        if (array_key_exists($ability, $this->abilities)) {
            return call_user_func_array($this->abilities[$ability],
                array_merge(array(call_user_func($this->userResolver)), array_slice(func_get_args(), 1)));
        }
        return false;
    }

    /**
     * 返回一个指定User的Gate实例
     * @return static
     */
    public function forUser($user)
    {
        $callback = function () use ($user) {
            return $user;
        };

        return new static($callback, $this->abilities);
    }
}
