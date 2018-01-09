<?php

namespace Leaf\Log;

use Psr\Log\LoggerInterface;

abstract class LogFilter implements LoggerInterface
{
    protected $messages = array();

    public function __construct(array $config = array())
    {
        foreach ($config as $key => $value) {
            $this->$key = $value;
        }
    }

    public abstract function export();

    public function write($level, $message, array $context = array(), $channel = null)
    {
        $inFile = '';
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 4);

        if (count($backtrace) > 3) {
            $inFile = isset($backtrace[3]['file']) ? $backtrace[3]['file'] : '';
            $inFile .= ':' . (isset($backtrace[3]['line']) ? $backtrace[3]['line'] : '');
        }

        $time = date('Y-m-d H:i:s');

        $log = static::varToString($message) . "\n";
        $log .= $inFile === '' ? '' : ("File\t" . $inFile . "\n");
        if (!empty($_SERVER['REQUEST_URI'])) {
            $log .= "REQUEST_URI\t" . $_SERVER['REQUEST_URI'];
        }
        if (!empty($_SERVER['HTTP_REFERER'])) {
            $log .= ("\n" . "HTTP_REFERER\t" . $_SERVER['HTTP_REFERER']);
        }

        $this->messages = [];
        $this->messages[] = array('channel' => $channel, 'level' => $level, 'message' => $log, 'datetime' => $time, 'context' => $context);

        $this->export();
    }

    public function varToString($var)
    {
        if (is_object($var)) {
            return sprintf('Object(%s)', get_class($var));
        }

        if (is_array($var)) {
            $arr = array();
            foreach ($var as $k => $v) {
                $arr[] = sprintf('%s => %s', $k, static::varToString($v));
            }

            return sprintf("Array(%s)", implode(', ', $arr));
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

    public function debug($message, array $context = array())
    {
        static::write('debug', $message, $context);
    }

    public function info($message, array $context = array())
    {
        static::write('info', $message, $context);
    }

    public function warning($message, array $context = array())
    {
        static::write('warning', $message, $context);
    }

    public function error($message, array $context = array())
    {
        static::write('error', $message, $context);
    }

    public function emergency($message, array $context = array())
    {
        static::write('emergency', $message, $context);
    }

    public function alert($message, array $context = array())
    {
        static::write('alert', $message, $context);
    }

    public function critical($message, array $context = array())
    {
        static::write('critical', $message, $context);
    }

    public function notice($message, array $context = array())
    {
        static::write('notice', $message, $context);
    }

    public function log($level, $message, array $context = array())
    {
        static::write(strtolower($level), $message, $context);
    }
}