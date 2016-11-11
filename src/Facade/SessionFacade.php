<?php

namespace Leaf\Facade;

use Leaf\Application;

/**
 * Session Facade
 * @author  Zou Yiliang
 * @since   1.0
 */
class SessionFacade
{
    public static function __callStatic($name, $arguments)
    {
        return call_user_func_array(array(Application::$app['session'], $name), $arguments);
    }
}