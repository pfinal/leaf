<?php

namespace Leaf\Provider;

use Pimple\ServiceProviderInterface;

class QueueProvider implements ServiceProviderInterface
{
    /**
     * @param \PFinal\Container\Container $app
     */
    public function register(\Pimple\Container $app)
    {
        $app['queue'] = function () use ($app) {
            $config = isset($app['queue.config']) ? $app['queue.config'] : array();
            $config += array('class' => 'PFinal\Queue\Driver\Sync');

            if (!isset($config['dbConfig'])) {
                $config['dbConfig'] = $app['db.config'];
            }

            $class = $config['class'];
            unset($config['class']);
            return $app->make($class, array('config' => $config));
        };
    }
}