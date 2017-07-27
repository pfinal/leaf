<?php

namespace Leaf {

    use Closure;
    use Leaf\Auth\AuthManager;
    use Leaf\Facade\MailFacade;
    use Leaf\Facade\QueueFacade;
    use PFinal\Database\Builder;
    use PFinal\Routing\Router;
    use PFinal\Session\NativeSession;
    use PFinal\Session\SessionInterface;
    use PFinal\Cache\CacheInterface;

    class Route
    {
        public static function annotation($controller)
        {
            return (new Route())->annotation($controller);
        }

        public static function get($path, $callback, $middleware = array())
        {
            return (new Route())->get($path, $callback, $middleware);
        }

        public static function post($path, $callback, $middleware = array())
        {
            return (new Route())->post($path, $callback, $middleware);
        }

        public static function any($path, $callback, $middleware = array())
        {
            return (new Route())->any($path, $callback, $middleware);
        }

        public static function group(array $attributes, \Closure $callback)
        {
            return (new Route())->group($attributes, $callback);
        }
    }

    class Session
    {
        /**
         * 储存数据到Session中
         * @param string $key
         * @param $value mixed
         */
        public static function set($key, $value)
        {
            (new NativeSession())->set($key, $value);
        }

        /**
         * 从Session中取回数据
         * @param string $key
         * @param null $defaultValue 如果对应key不存在时，返回此值
         * @return mixed
         */
        public static function get($key, $defaultValue = null)
        {
            return (new NativeSession())->get($key, $defaultValue);
        }

        /**
         * 从Session中删除该数据
         * @param string $key
         * @return mixed 返回被删除的数据，不存在时返回null
         */
        public static function remove($key)
        {
            return (new NativeSession())->remove($key);
        }

        /**
         * 清空Session中所有数据
         */
        public static function clear()
        {
            (new NativeSession())->clear();
        }

        /**
         * 放入闪存数据(Flash Data)到Session中
         * @param string $key
         * @param mixed $value
         */
        public static function setFlash($key, $value)
        {
            (new NativeSession())->setFlash($key, $value);
        }

        /**
         * Session中是否存在闪存(Flash Data)数据
         * @param string $key
         * @return bool
         */
        public static function hasFlash($key)
        {
            return (new NativeSession())->hasFlash($key);
        }

        /**
         * 从Session中获取闪存数据(获取后该数据将从Session中被删除)
         * @param string $key
         * @param null $defaultValue
         * @return mixed
         */
        public static function getFlash($key, $defaultValue = null)
        {
            return (new NativeSession())->getFlash($key, $defaultValue);
        }

    }

    class Mail extends MailFacade
    {

    }

    class Queue extends QueueFacade
    {
        /**
         * 推送一个新任务到队列中
         * @param string $class 处理任务的类的名称，默认调用fire方法。自定义方法示例：'SendEmail@send'。
         *            fire方法接受一个 Job 实例对像 和一个data 数组
         *            public function fire($job, $data){
         *                //处理这个job ...
         *                //当处理完成，从队列中将它删除
         *                $job->delete();
         *                //或处理失败时，将一个任务放回队列
         *                $job->release();
         *            }
         * @param mixed $data 需要传递给处理器的数据
         * @return int
         */
        public static function push($class, $data = null, $queue = null)
        {
        }
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
            return (new Builder())->getConnection();
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
        public static function table($tableName = '')
        {
            return (new Builder())->table($tableName);
        }

        /**
         * 在一个 try/catch 块中执行给定的回调，如果回调用没有抛出任何异常，将自动提交事务
         *
         * 如果捕获到任何异常, 将自动回滚事务后，继续抛出异常
         *
         * @param  \Closure $callback
         * @param  int $attempts 事务会重试的次数。如果重试结束还没有成功执行，将会抛出一个异常
         * @return mixed
         *
         * @throws \Exception|\Throwable
         */
        public function transaction(Closure $callback, $attempts = 1)
        {
            return (new Builder())->transaction($callback, $attempts);
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
         * @param int $expire 缓存过期时间(多少秒后过期)，0表示永不过期.
         * @return bool
         */
        public static function set($id, $value, $expire)
        {
        }

        /**
         * @param $id
         * @param $value
         * @param int $expire 缓存过期时间(多少秒后过期)，0表示永不过期.
         * @return bool
         */
        public static function add($id, $value, $expire)
        {
        }

        /**
         * @return bool
         */
        public static function delete($id)
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