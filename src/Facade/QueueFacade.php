<?php

namespace Leaf\Facade;

use Leaf\Application;

class QueueFacade
{
    public static function __callStatic($method, $arguments)
    {
        $obj = Application::$app['queue'];
        if (method_exists($obj, $method)) {
            return call_user_func_array([$obj, $method], $arguments);
        }

        throw new \Exception('Call to undefined method ' . __CLASS__ . '::' . $method . '()');
    }
}