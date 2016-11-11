<?php

namespace Leaf\Provider;

use Leaf\Application;
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
        $app['log'] = function () use ($app) {

            $target = [
                'file' => 'Leaf\Log\FileTarget',
                'db' => 'Leaf\Log\DbTarget',
            ];

            $key = isset($app['log.target']) ? $app['log.target'] : 'file';

            if (array_key_exists($key, $target)) {
                $class = $target[$key];
            } else {
                throw new \Exception(sprintf('Log target driver "%s" does not exist.', $key));
            }

            $param = isset($app['queue.config']) ? $app['queue.config'] : array();

            $filter = new \Leaf\Log\LogFilter();
            /* @var $app Application */
            $filter->target = $app->make($class, $param);
            return $filter;
        };
    }
}