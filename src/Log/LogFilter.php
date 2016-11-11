<?php

namespace Leaf\Log;

use Leaf\Application;

/**
 * 日志
 * $app->register(new \Rain\Provider\LogServiceProvider()); //默认记录到文件
 * $app->register(new \Rain\Provider\LogServiceProvider(), ['log.target' => 'db']);
 */
class LogFilter
{

    public $target;

    /**
     * @return string|null
     */
    protected function getLogFile($type)
    {
        if (isset(Application::$app['log.path'])) {
            $path = rtrim(Application::$app['log.path'], '/\\') . '/';
        } else {
            $path = rtrim(Application::$app->getRuntimePath(), '/\\') . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR;
        }

        $file = $path . lcfirst($type) . '.log';

        if (!file_exists(dirname($file))) {
            if (!@mkdir(dirname($file), 0777, true)) {
                return null;
            }
        }
        return $file;
    }

    public function debug($msg)
    {
        static::write('Debug', $msg);
    }

    public function info($msg)
    {
        static::write('Info', $msg);
    }

    public function warning($msg)
    {
        static::write('Warning', $msg);
    }

    public function error($msg)
    {
        static::write('Error', $msg);
    }

    public function write($type, $msg)
    {
        $inFile = '';
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 4);

        if (count($backtrace) > 3) {
            $inFile = isset($backtrace[3]['file']) ? $backtrace[3]['file'] : '';
            $inFile .= ':' . (isset($backtrace[3]['line']) ? $backtrace[3]['line'] : '');
        }

        $log = date('Y-m-d H:i:s') . "\n";
        $log .= $type . "\t" . static::varToString($msg) . "\n";
        $log .= $inFile === '' ?: "File\t" . $inFile . "\n";
        if (isset($_SERVER['REQUEST_URI'])) {
            $log .= "REQUEST_URI\t" . $_SERVER['REQUEST_URI'];
        }
        if (isset($_SERVER['HTTP_REFERER'])) {
            $log .= ("\n" . "HTTP_REFERER\t" . $_SERVER['HTTP_REFERER']);
        }

        $log .= "\n\n";

        $messages = [[$log, $type, '', time()]];

        $target = [
            'file' => 'Leaf\Log\FileTarget',
            'db' => 'Leaf\Log\DbTarget',
        ];

        $app = Application::$app;
        $key = isset($app['log.target']) ? $app['log.target'] : 'file';

        if (array_key_exists($key, $target)) {
            $class = $target[$key];
        } else {
            throw new \Exception(sprintf('Log target driver "%s" does not exist.', $key));
        }

        $param = isset($app['log.config']) ? $app['log.config'] : array();

        $target = new $class($param);
        $target->messages = $messages;

        if ($key == 'file') {
            $target->messages = [$log];
            $target->logFile = static::getLogFile($type);
        }

        $target->export();
    }


    public function varToString($var)
    {
        if (is_object($var)) {
            return sprintf('Object(%s)', get_class($var));
        }

        if (is_array($var)) {
            $a = array();
            foreach ($var as $k => $v) {
                $a[] = sprintf('%s => %s', $k, static::varToString($v));
            }

            return sprintf("Array(%s)", implode(', ', $a));
        }

        if (is_resource($var)) {
            return sprintf('Resource(%s)', get_resource_type($var));
        }

        if (null === $var) {
            return 'null';
        }

        if (false === $var) {
            return 'false';
        }

        if (true === $var) {
            return 'true';
        }

        return (string)$var;
    }
}