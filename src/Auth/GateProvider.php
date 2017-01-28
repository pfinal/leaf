<?php

namespace Leaf\Auth;

use Pimple\Container;
use Pimple\ServiceProviderInterface;

/**
 * $app->register(new \Leaf\Auth\GateProvider());
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
            $config += array('class' => 'Leaf\Auth\Gate', 'authClass' => 'Service\Auth'); // Service\Auth extends \Leaf\AuthManager
            $config += array('userResolver' => function () use ($app, $config) {
                $authClass = $config['authClass'];
                return forward_static_call(array($authClass, 'getUser'));
            });
            $class = $config['class'];
            unset($config['class']);
            return $app->make($class, $config);
        };
    }
}