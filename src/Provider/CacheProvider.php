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
        $app['PFinal\\Cache\\CacheInterface'] = $app['cache'] = function () use ($app) {
            $config = isset($app['cache.config']) ? $app['cache.config'] : array();
            $config += array('class' => 'PFinal\Cache\FileCache', 'cachePath' => $app->getRuntimePath() . DIRECTORY_SEPARATOR . 'cache');
            $class = $config['class'];
            unset($config['class']);
            return $app->make($class, array('config' => $config));
        };

    }
}