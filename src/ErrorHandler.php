<?php

namespace Leaf;

use PFinal\Routing\Exception\ExceptionInterface;

/**
 * 错误和异常处理
 * @author  Zou Yiliang
 */
class ErrorHandler
{
    protected $_error = array();

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

        if (!headers_sent()) {
            header('Access-Control-Allow-Origin: *');
            header("HTTP/1.0 500 Internal Server Error", true, 500);
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

        if ($ex instanceof ExceptionInterface) {
            $this->_error['code'] = 404;
            $this->_error['trace'] = $ex->getTraceAsString();
        } else {
            $this->_error['code'] = 500;
            $this->_error['trace'] = $ex->getTraceAsString();
        }

        $this->_error['message'] = $ex->getMessage();
        $this->_error['file'] = $ex->getFile();
        $this->_error['line'] = $ex->getLine();

        $data = $this->_error;

        if (!headers_sent()) {
            header('Access-Control-Allow-Origin: *');
            header("HTTP/1.0 {$data['code']} " . $this->getHttpHeader($data['code'], get_class($ex)), true, $this->_error['code']);
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

        if (isset(Application::$app['log'])) {
            Application::$app['log']->write('app', $log);
        }

        if (php_sapi_name() === 'cli') {
            echo "\n";
            echo $this->_error['type'] . ' ' . $this->_error['message'] . "\n";
            echo $this->_error['file'] . '(' . $this->_error['line'] . ")\n\n";
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
}