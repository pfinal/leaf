<?php

namespace Leaf\Facade;

use Leaf\Application;

/**
 * LogFacade
 * @author  Zou Yiliang
 * @since   1.0
 */
class LogFacade
{
    public static function __callStatic($name, $arguments)
    {
        $log = Application::$app['log'];
        if (method_exists($log, $name)) {
            return call_user_func_array([$log, $name], $arguments);
        }

        throw new \Exception('Call to undefined method ' . __CLASS__ . '::' . $name . '()');
    }
}