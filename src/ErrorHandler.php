<?php

namespace Leaf;

use Leaf\Exception\HttpException;
use PFinal\Routing\Exception\ExceptionInterface;
use PFinal\Database\NotFoundException;

/**
 * 错误和异常处理
 */
class ErrorHandler
{
    protected $_error = array();

    protected $writeToFile = true;

    function handleError($num, $message, $file, $line)
    {
        //避免@失效
        if (!(error_reporting() & $num)) {
            return;
        }

        restore_error_handler();

        $this->_error['code'] = 500;
        $this->_error['message'] = $message;
        $this->_error['file'] = $file;
        $this->_error['line'] = $line;

        $log = '';
        switch ($num) {
            case E_ERROR:
            case E_USER_ERROR:
                $this->_error['type'] = 'Error';
                break;

            case E_WARNING:
            case E_USER_WARNING:
                $this->_error['type'] = 'Warning';
                break;

            case E_NOTICE:
            case E_USER_NOTICE:
                $this->_error['type'] = 'Notice';
                break;

            default:
                $this->_error['type'] = 'Unknown error type: ' . $num . '.';
                break;
        }

        $trace = debug_backtrace();

        if (count($trace) > 1) {
            $trace = array_slice($trace, 1);
        }
        foreach ($trace as $i => $t) {
            if (!isset($t['file']))
                $t['file'] = 'unknown';
            if (!isset($t['line']))
                $t['line'] = 0;
            if (!isset($t['function']))
                $t['function'] = 'unknown';
            $log .= "#$i {$t['file']}({$t['line']}): ";
            if (isset($t['object']) && is_object($t['object']))
                $log .= get_class($t['object']) . '->';
            $log .= "{$t['function']}()\n";

        }
        if (isset($_SERVER['REQUEST_URI'])) {
            $log .= 'REQUEST_URI=' . $_SERVER['REQUEST_URI'];
        }

        $this->_error['trace'] = $log;

        if (!$this->isAjax()) {
            if (!headers_sent()) {
                header('Access-Control-Allow-Origin: *');
                header("HTTP/1.0 500 Internal Server Error", true, 500);
            }
        }

        $this->render();
    }

    /**
     * @param \Exception $ex
     */
    public function handleException($ex)
    {
        restore_exception_handler();

        $this->_error['type'] = get_class($ex);

        if ($ex instanceof HttpException) {                    //业务逻辑抛出的异常
            $this->_error['code'] = $ex->getStatusCode();
            $this->_error['trace'] = $this->getTraceString($ex);//$ex->getTraceAsString();
            $this->writeToFile = false;
        } else if ($ex instanceof ExceptionInterface) {        //路由异常(页面不存在或方法不允许)
            $this->_error['code'] = 404;
            $this->_error['trace'] = $this->getTraceString($ex);// $ex->getTraceAsString();
            $this->writeToFile = false;
        } else if ($ex instanceof NotFoundException) {
            $this->_error['code'] = 404;
            $this->_error['trace'] = $this->getTraceString($ex);// $ex->getTraceAsString();
            $this->writeToFile = false;
        } else {
            $this->_error['code'] = 500;                       // Internal Server Error
            $this->_error['trace'] = $this->getTraceString($ex);// $ex->getTraceAsString();
        }

        $this->_error['message'] = $ex->getMessage();
        $this->_error['file'] = $ex->getFile();
        $this->_error['line'] = $ex->getLine();

        $data = $this->_error;

        if (!$this->isAjax()) {
            if (!headers_sent()) {
                header('Access-Control-Allow-Origin: *');
                header("HTTP/1.0 {$data['code']} " . $this->getHttpHeader($data['code'], get_class($ex)), true, $this->_error['code']);
            }
        }

        $this->render();
    }

    protected function render()
    {
        $log = $this->_error['message'] . "\n";
        $log .= "File\t" . $this->_error['file'] . ':' . $this->_error['line'] . "\n";
        if (isset($_SERVER['REQUEST_URI'])) {
            $log .= "URL\t" . $_SERVER['REQUEST_URI'];
        }
        $log .= "\n";
        $log .= "Trace\n" . $this->_error['trace'];

        if ($this->writeToFile) {
            $logger = new \Leaf\Log\FileTarget();
            $logger->write('sys', $log);
        }

        if (php_sapi_name() === 'cli') {
            echo "\n";
            echo $this->_error['type'] . ' ' . $this->_error['message'] . "\n";
            echo $this->_error['file'] . '(' . $this->_error['line'] . ")\n\n";
            exit;
        }

        if ($this->isAjax()) {
            header('Content-Type: application/json; charset=UTF-8');
            echo Json::encode(['status' => false, 'data' => $this->_error['message'], 'code' => 'error:' . $this->_error['code']]);
            exit;
        }

        if (!Application::$app['debug']) {

            // error404.twig, or error40x.twig, or error4xx.twig, or error.twig

            $code = $this->_error['code'];
            $templates = array(
                'error' . $code . '.twig',
                'error' . substr($code, 0, 2) . 'x.twig',
                'error' . substr($code, 0, 1) . 'xx.twig',
                'error.twig',
            );

            echo Application::$app['twig']->resolveTemplate($templates)->render(array(
                'code' => $code,
                'message' => $this->_error['message'],
            ));

            exit;
        }

        $trace = '';

        if (array_key_exists('trace', $this->_error)) {
            $trace = nl2br($this->_error['trace']);
            $trace = "<div class='main'><h2>Stack Trace</h2><div>{$trace}</div></div>";
        }

        $content = nl2br(htmlspecialchars($this->_error['message']));

        $mem = $this->convert(memory_get_usage(true));
        $ver = @date('Y-m-d H:i:s');

        echo <<<TAG
<!DOCTYPE html PUBLIC
	"-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>A PHP Error was encountered</title>
<style type="text/css">
/*<![CDATA[*/
body {font-family:"Verdana";font-weight:normal;color:black;background-color:white;}
h1 { font-family:"Verdana";font-weight:normal;font-size:18pt;color:maroon; }
h2 { font-family:"Verdana";font-weight:normal;font-size:12pt;color:maroon }
h3 {font-family:"Verdana";font-weight:bold;font-size:11pt}
p {font-family:"Verdana";font-weight:normal;color:black;font-size:9pt;margin-top: -5px}
.main{width:90%;border:1px solid #ccc;margin:20px auto;padding:5px 10px;}
.version{width:90%;margin:0 auto;border-top:1px solid #ccc;padding:5px 10px;}
/*]]>*/
</style>
</head>
<body>
<div class="main">
	<h1>A PHP Error was encountered</h1>
	<h2>{$this->_error['type']} {$content}</h2>
	<h2>File: {$this->_error['file']}</h2>
	<h2>Line: {$this->_error['line']}</h2>
</div>
{$trace}
<div class="main">
	<h2>Memory Usage</h2>
	<div>{$mem}</div>
</div>
<div class="version">{$ver}</div>
</body>
</html>
TAG;
        exit;
    }

    protected function getHttpHeader($httpCode, $replacement = '')
    {
        $httpCodes = array(
            100 => 'Continue',
            101 => 'Switching Protocols',
            102 => 'Processing',
            118 => 'Connection timed out',
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            207 => 'Multi-Status',
            210 => 'Content Different',
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found',
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            307 => 'Temporary Redirect',
            310 => 'Too many Redirect',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Time-out',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Requested range unsatisfiable',
            417 => 'Expectation failed',
            418 => 'I’m a teapot',
            422 => 'Unprocessable entity',
            423 => 'Locked',
            424 => 'Method failure',
            425 => 'Unordered Collection',
            426 => 'Upgrade Required',
            449 => 'Retry With',
            450 => 'Blocked by Windows Parental Controls',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway ou Proxy Error',
            503 => 'Service Unavailable',
            504 => 'Gateway Time-out',
            505 => 'HTTP Version not supported',
            507 => 'Insufficient storage',
            509 => 'Bandwidth Limit Exceeded',
        );
        if (isset($httpCodes[$httpCode])) {
            return $httpCodes[$httpCode];
        } else {
            return $replacement;
        }
    }

    public function convert($size)
    {
        $unit = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
        return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
    }

    protected function isAjax()
    {
        $str = isset($_SERVER['HTTP_X_REQUESTED_WITH']) ? $_SERVER['HTTP_X_REQUESTED_WITH'] : '';
        return strtolower($str) == 'xmlhttprequest';
    }

    /**
     * @param \Exception $ex
     * @return string
     */
    public function getTraceString($ex)
    {
        $traceInfo = [];
        foreach ($ex->getTrace() as $ind => $trace) {

            if (isset($trace['file'])) {
                $file = substr($trace['file'], strlen(Application::$app['path']));
            } else {
                $file = '[internal function]';
            }

            if (strpos($file, '/pfinal/routing/src/Router.php') !== false) {
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

    public function varToString($var)
    {
        if (is_object($var)) {
            if ($var instanceof Object) {
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

    private function replaceNewlines($str)
    {
        return str_replace(array("\r\n", "\r", "\n"), ' ', $str);
    }

    private function jsonEncode($data)
    {
        if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
            return @json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        return @json_encode($data);
    }
}