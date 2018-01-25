<?php

namespace Leaf\Middleware;

use Leaf\Application;
use Leaf\Exception\MaintenanceModeException;
use Leaf\Request;
use Leaf\Response;

/**
 * 检查是否维护模式
 */
class CheckForMaintenanceMode
{
    /**
     * @param Response $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, \Closure $next)
    {
        $file = Application::$app->getRuntimePath('down');

        if (file_exists($file)) {

            $data = json_decode(file_get_contents($file), true);

            throw new MaintenanceModeException($data['time'], $data['retry'], $data['message']);
        }

        return $next($request);
    }
}