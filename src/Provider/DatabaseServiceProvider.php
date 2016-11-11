<?php

namespace Leaf\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;

/**
 * 数据库
 * @author  Zou Yiliang
 */
class DatabaseServiceProvider implements ServiceProviderInterface
{
    /**
     * 在容器中注册服务
     *
     * @param Container $app
     */
    public function register(Container $app)
    {
        $app['db'] = function () use ($app) {
            return new \PFinal\Database\Builder($app['db.config']);
        };
    }
}