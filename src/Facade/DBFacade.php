<?php

namespace Leaf\Facade;

use Leaf\Application;

/**
 * DBFacade
 * @author  Zou Yiliang
 * @since   1.0
 */
class DBFacade
{
    public static function __callStatic($name, $arguments)
    {
        $db = Application::$app['db'];

        if (method_exists($db, $name)) {
            return call_user_func_array([$db, $name], $arguments);
        }

        $connection = $db->getConnection();
        if (method_exists($connection, $name)) {
            return call_user_func_array([$connection, $name], $arguments);
        }
        throw new \Exception('Call to undefined method ' . __CLASS__ . '::' . $name . '()');
    }
}