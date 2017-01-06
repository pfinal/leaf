<?php

namespace Leaf\Auth;

use Pimple\Container;
use Pimple\ServiceProviderInterface;

/**
 * $app->register(new \Leaf\Auth\GateProvider(), ['auth' => 'MyAuth']);  // class MyAuth extends \Leaf\AuthManager
 *
 * @package Leaf\Auth
 */
class GateProvider implements ServiceProviderInterface
{
    /**
     * 在容器中注册服务
     *
     * @param Container $app
     */
    public function register(Container $app)
    {
        $app['gate'] = function () use ($app) {
            $config = isset($app['gate.config']) ? $app['gate.config'] : array();
            $config += array('class' => 'Leaf\Auth\Gate', 'userResolver' => function () use ($app) {
                $authClass = $app['auth'];
                return forward_static_call([$authClass, 'getUser']);
            });
            $class = $config['class'];
            unset($config['class']);
            return $app->make($class, $config);
        };
    }
}