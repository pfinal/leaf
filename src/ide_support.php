<?php

namespace Leaf {

    use Leaf\Facade\MailFacade;
    use PFinal\Session\SessionInterface;
    use PFinal\Cache\CacheInterface;

    class Route
    {
        public static function annotation($controller)
        {
        }

        public static function get($path, $callback, $middleware = array())
        {
        }

        public static function post($path, $callback, $middleware = array())
        {
        }

        public static function any($path, $callback, $middleware = array())
        {
        }

        public static function group(array $attributes, \Closure $callback)
        {
        }
    }

    class Session implements SessionInterface
    {
        /**
         * 储存数据到Session中
         * @param string $key
         * @param $value mixed
         */
        public static function set($key, $value)
        {
        }

        /**
         * 从Session中取回数据
         * @param string $key
         * @param null $defaultValue 如果对应key不存在时，返回此值
         * @return mixed
         */
        public static function get($key, $defaultValue = null)
        {
        }

        /**
         * 从Session中删除该数据
         * @param string $key
         * @return mixed 返回被删除的数据，不存在时返回null
         */
        public static function remove($key)
        {
        }

        /**
         * 清空Session中所有数据
         */
        public static function clear()
        {
        }

        /**
         * 放入闪存数据(Flash Data)到Session中
         * @param string $key
         * @param mixed $value
         */
        public static function setFlash($key, $value)
        {
        }

        /**
         * Session中是否存在闪存(Flash Data)数据
         * @param string $key
         * @return bool
         */
        public static function hasFlash($key)
        {
        }

        /**
         * 从Session中获取闪存数据(获取后该数据将从Session中被删除)
         * @param string $key
         * @param null $defaultValue
         * @return mixed
         */
        public static function getFlash($key, $defaultValue = null)
        {
        }

    }

    class Mail extends MailFacade
    {

    }

    class Log
    {
        public static function debug($var)
        {
        }

        public static function error($var)
        {
        }

        public static function warning($var)
        {
        }

        public static function info($var)
        {
        }
    }

    class DB
    {
        /**
         * @return \PFinal\Database\Connection
         */
        public static function getConnection()
        {
        }

        /**
         * 返回数据库操作对象
         * 此方法将自动添加表前缀, 例如配置的表前缀为"cms_", 则"user"将被替换为 "cms_user", 相当于"{{%user}}"
         * 如果希望使用后缀，使用'{{user%}}'
         * 如果不希望添加表前缀，使用'{{user}}'
         * 如果使用自定义表前缀，使用'{{wp_user}}'
         * @param string $tableName
         * @return \PFinal\Database\Builder
         */
        public static function table($tableName)
        {
        }

    }

    class Cache implements CacheInterface
    {
        /**
         * @return mixed | false
         */
        public static function get($id)
        {
        }

        /**
         * @return array | false
         */
        public static function mget($ids)
        {
        }

        /**
         * @param $id
         * @param mixed $value
         * @param int $expire 缓存过期时间(多少秒后过期)。0表示永不过期.
         * @return bool
         */
        public static function set($id, $value, $expire = 0)
        {
        }

        /**
         * @param $id
         * @param $value
         * @param int $expire 缓存过期时间(多少秒后过期)，如果大于30天，请使用UNIX时间戳。0表示永不过期.
         * @return bool
         */
        public static function add($id, $value, $expire = 0)
        {
        }

        /**
         * @return bool
         */
        public static function delete($id)
        {
        }

        /**
         * @return bool
         */
        public static function flush()
        {
        }
    }

    class Response extends \Symfony\Component\HttpFoundation\Response
    {
    }

    class RedirectResponse extends \Symfony\Component\HttpFoundation\RedirectResponse
    {
    }

}