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
     * @param Container $app
     */
    public function register(Container $app)
    {
        $app['session'] = function () use ($app) {
            $config = isset($app['session.config']) ? $app['session.config'] : array();
            $config += array('class' => 'PFinal\Session\NativeSession');
            $class = $config['class'];
            unset($config['class']);
            return $app->make($class, $config);
        };
    }
}