<?php

namespace Leaf\Provider;

use Leaf\Application;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class SessionProvider implements ServiceProviderInterface
{
    /**
     * 在容器中注册服务
     *
     * 使用Redis存储Session:
     * $app->register(new \Leaf\Provider\SessionProvider(), ['session.config' => [
     *     'class' => 'PFinal\Session\RedisSession',
     *     'server' => [
     *         'scheme' => 'tcp',
     *         'host' => '192.168.0.2',
     *         'port' => 6379,
     *     ]
     * ]]);
     *
     * @param Container $app
     */
    public function register(Container $app)
    {
        $app['session'] = function () use ($app) {
            $config = isset($app['session.config']) ? $app['session.config'] : array();
            $config += array('class' => 'PFinal\Session\NativeSession');
            $class = $config['class'];
            unset($config['class']);
            return $app->make($class, array('config' => $config));
        };
    }
}