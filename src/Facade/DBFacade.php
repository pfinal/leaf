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
        $db = Application::$app['PFinal\Database\Builder'];

        if (method_exists($db, $name)) {
            return call_user_func_array([$db, $name], $arguments);
        }

        $connection = $db->getConnection();
        if (method_exists($connection, $name)) {
            return call_user_func_array([$connection, $name], $arguments);
        }
        throw new \Exception('Call to undefined method ' . __CLASS__ . '::' . $name . '()');
    }

    /**
     * 通过指定的DB操作数据库
     *
     * 使用前先配置
     *
     * $app['db2'] = function () use ($app) {
     *     return new \PFinal\Database\Builder([
     *         'dsn' => 'mysql:host=localhost;dbname=yuntu',
     *         'username' => 'root',
     *         'password' => '',
     *         'charset' => 'utf8mb4',
     *         'tablePrefix' => '',
     *     ]);
     * };
     *
     * @param string $db 例如 'db2'
     * @return \PFinal\Database\Builder
     */
    public static function via($db)
    {
        return Application::$app[$db];
    }
}