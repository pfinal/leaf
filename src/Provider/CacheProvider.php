<?php

namespace Leaf\Provider;

use Leaf\Application;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class CacheProvider implements ServiceProviderInterface
{
    /**
     * 在容器中注册服务
     *
     * @param Application $app
     */
    public function register(Container $app)
    {
        $app['cache'] = function () use ($app) {
            $class = isset($app['cache.class']) ? $app['cache.class'] : 'PFinal\Cache\FileCache';
            $session = $app->make($class);
            return $session;
        };
    }
}