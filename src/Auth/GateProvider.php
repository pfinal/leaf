<?php

namespace Leaf\Auth;

use Pimple\Container;
use Pimple\ServiceProviderInterface;

/**
 * 权限
 *
 * $app->register(new \Leaf\Auth\GateProvider(), ['gate.config'=>['authClass' => 'Auth']]);
 *
 * $app['gate'] = $app->extend('gate', function ($gate, $app) {
 *     // @var  $app \Leaf\Application
 *     // @var $gate  \Leaf\Auth\Gate
 *
 *     $gate->define('delete', function (\Entity\User $user) {
 *         return $user->isSuper();
 *     });
 *
 *     $gate->define('update', function (\Entity\User $user, $post) {
 *         return $user->isSuper() || $post->user_id = $user->id;
 *     });
 *
 *     return $gate;
 *
 *  });
 *
 * //判断是否有权限
 * if($user->can('delete')){ // delete... }
 * if($user->can('update', $post)){ //update ... }
 *
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