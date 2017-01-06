<?php

namespace Leaf\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;

class CacheProvider implements ServiceProviderInterface
{
    /**
     * 在容器中注册服务
     *
     * @param Container $app
     */
    public function register(Container $app)
    {
        $app['cache'] = function () use ($app) {
            $config = isset($app['cache.config']) ? $app['cache.config'] : array();
            $config += array('class' => 'PFinal\Cache\FileCache');
            $class = $config['class'];
            unset($config['class']);
            return $app->make($class, $config);
        };
    }
}