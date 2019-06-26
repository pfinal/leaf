<?php

namespace Leaf\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use PFinal\Database\Builder;

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
        $app['PFinal\Database\Builder'] = function () use ($app) {
            return new Builder($app['db.config']);
        };

        Builder::setContainer($app);
    }
}