<?php

namespace Leaf\Facade;

use Leaf\Application;

class CacheFacade
{
    public static function __callStatic($name, $arguments)
    {
        /**
         * @var $cache \PFinal\Cache\CacheInterface
         */
        $cache = Application::$app['cache'];
        if (method_exists($cache, $name)) {
            return call_user_func_array([$cache, $name], $arguments);
        }

        throw new \Exception('Call to undefined method ' . __CLASS__ . '::' . $name . '()');
    }
}