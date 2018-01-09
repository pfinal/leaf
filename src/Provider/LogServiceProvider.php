<?php

namespace Leaf\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;

/**
 * 日志
 * @author  Zou Yiliang
 */
class LogServiceProvider implements ServiceProviderInterface
{
    /**
     * 在容器中注册服务
     *
     * @param Container $app
     */
    public function register(Container $app)
    {
        $app['Psr\\Log\\LoggerInterface'] = $app['log'] = function () use ($app) {
            $config = isset($app['log.config']) ? $app['log.config'] : array();
            $config += array('class' => 'Leaf\Log\Logger');
            $class = $config['class'];
            unset($config['class']);
            return $app->make($class, array('config' => $config));
        };
    }
}