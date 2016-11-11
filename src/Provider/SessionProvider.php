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
     * @param Application $app
     */
    public function register(Container $app)
    {
        $app['session'] = function () use ($app) {
            $class = isset($app['session.class']) ? $app['session.class'] : 'PFinal\Session\NativeSession';
            $session = $app->make($class);
            return $session;
        };
    }
}