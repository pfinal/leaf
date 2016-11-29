<?php

namespace Leaf\Auth;

use Leaf\Application;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

/**
 * $app->register(new \Leaf\Auth\GateProvider());
 * $app->register(new \Leaf\Auth\GateProvider(), ['auth.class' => 'AuthAdmin']);  // AuthAdmin extends |Leaf\Auth
 *
 * @package Leaf\Auth
 */
class GateProvider implements ServiceProviderInterface
{
    /**
     * 在容器中注册服务
     *
     * @param Application $app
     */
    public function register(Container $app)
    {
        $app['gate'] = function () use ($app) {
            return new Gate(function () use ($app) {

                $auth = isset($app['auth.class']) ? $app['auth.class'] : 'Leaf\\Auth';

                return forward_static_call([$auth, 'getUser']);
            });
        };
    }
}