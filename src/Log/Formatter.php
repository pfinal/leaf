<?php

namespace Leaf\Log;

use Monolog\Formatter\LineFormatter;

class Formatter extends LineFormatter
{
    /**
     * {@inheritdoc}
     */
    public function format(array $record)
    {
        $vars = parent::normalize($record);

        //PSR-3 日志接口规范 如需通过上下文参数传入了一个 Exception 对象， 必须以 'exception' 作为键名
        $exception = isset($vars['context']['exception']) ? $vars['context']['exception'] : null;
        unset($vars['context']['exception']);

        //[%datetime%] %channel%.%level_name%: %message%
        $output = sprintf("[%s] %s.%s: %s",
            $vars['datetime'],
            isset($vars['channel']) ? $this->stringify($vars['channel']) : '',
            isset($vars['channel']) ? $this->stringify($vars['level_name']) : '',
            isset($vars['channel']) ? $this->stringify($vars['message']) : ''
        );

        if (!empty($vars['context'])) {
            $output .= " " . $this->stringify($vars['context']);
        }

        if (!empty($vars['extra'])) {
            $output .= " " . $this->stringify($vars['extra']);
        }

        if (isset($exception['stacktrace'])) {
            $output .= "\n[stacktrace]\n" . $exception['stacktrace'] . "\n";
        }

        return $output . "\n";
    }


    protected function normalizeException($e)
    {
        if (!$e instanceof \Exception && !$e instanceof \Throwable) {
            throw new \InvalidArgumentException('Exception/Throwable expected, got ' . gettype($e) . ' / ' . get_class($e));
        }

        $data = array(
            'class' => get_class($e),
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'file' => $e->getFile() . ':' . $e->getLine(),
        );

        if ($e instanceof \SoapFault) {
            if (isset($e->faultcode)) {
                $data['faultcode'] = $e->faultcode;
            }

            if (isset($e->faultactor)) {
                $data['faultactor'] = $e->faultactor;
            }

            if (isset($e->detail)) {
                $data['detail'] = $e->detail;
            }
        }

        $stacktrace = $this->getTraceString($e);
        if (trim($stacktrace) != '') {
            $data['stacktrace'] = $stacktrace;
        }

        if ($previous = $e->getPrevious()) {
            $data['previous'] = $this->normalizeException($previous);
        }

        return $data;
    }

    /**
     * @param \Exception $ex
     * @return string
     */
    private function getTraceString($ex)
    {
        $traceInfo = [];
        foreach ($ex->getTrace() as $ind => $trace) {

            if (isset($trace['file'])) {
                $file = substr($trace['file'], strlen(\Leaf\Application::$app['path']));
            } else {
                $file = '[internal function]';
            }

            if (strpos($file, '/pfinal/routing/src/Router.php') !== false) {
                $traceInfo = array_slice($traceInfo, 0, count($traceInfo) - 1);
                break;
            }

            $args = [];
            foreach ($trace['args'] as $arg) {
                $args[] = $this->replaceNewlines($this->varToString($arg));
            }
            $traceInfo[] = sprintf('#%s %s%s: %s(%s)',
                $ind,
                $file,
                isset($trace['line']) ? ('(' . $trace['line'] . ')') : '',
                isset($trace['class']) ? ($trace['class'] . '::' . $trace['function']) : $trace['function'],
                join(', ', $args)
            );
        }

        return join("\n", $traceInfo);
    }

    private function varToString($var)
    {
        if (is_object($var)) {
            if ($var instanceof \Leaf\BaseObject) {
                return sprintf('#%s(%s)', get_class($var), $this->jsonEncode($var));
            }
            return sprintf('#%s', get_class($var));
        }

        if (is_array($var)) {
            $isIndexed = $this->isIndexed($var);
            $arr = array();
            foreach ($var as $k => $v) {
                if ($isIndexed) {
                    $arr[] = static::varToString($v);
                } else {
                    $arr[] = sprintf('%s=>%s', static::varToString($k), static::varToString($v));
                }
            }
            return sprintf("[%s]", implode(',', $arr));
        }

        if (is_resource($var)) {
            return sprintf('#resource(%s)', get_resource_type($var));
        }

        if (null === $var || is_bool($var)) {
            return var_export($var, true);
        }

        if (is_string($var)) {
            if (mb_strlen($var) > 500) {
                $var = mb_substr($var, 0, 500) . '...';
            }
            return "'" . addcslashes($var, "'\\\r\n") . "'";
        }

        return (string)$var;
    }

    /**
     * 是否索引数组
     * @param array $array
     * @return bool
     */
    private function isIndexed(array $array)
    {
        $keys = array_keys($array);
        return $keys === array_keys($keys);
    }

    private function jsonEncode($data)
    {
        if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
            return @json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        return @json_encode($data);
    }

}