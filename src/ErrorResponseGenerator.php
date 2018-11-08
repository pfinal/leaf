<?php

namespace Leaf;

use Leaf\Exception\HttpException;
use Symfony\Component\HttpFoundation\Response;

class ErrorResponseGenerator
{
    /**
     * Create/update the response representing the error.
     *
     * @param \Throwable $ex
     * @param \Leaf\Request $request
     * @return \Symfony\Component\HttpFoundation\Response | string
     * @throws \Throwable
     */
    public function __invoke($ex, $request = null)
    {
        $this->log($ex, $request);

        if (php_sapi_name() == 'cli') {
            return $this->renderCli($ex);
        }

        if ($this->isAjax()) {
            return $this->renderJson($ex);
        }

        return $this->renderHtml($ex);
    }

    protected function log($ex, $request = null)
    {
        if ($ex instanceof \ErrorException || $ex instanceof \LogicException) {
            Log::error(get_class($ex) . ' ' . $ex->getMessage(), ['exception' => $ex]);
        }
    }

    /**
     * @param \Throwable $ex
     * @return string
     */
    protected function renderCli($ex)
    {
        $str = "\n";
        $str .= get_class($ex) . ' ' . $ex->getMessage() . "\n";
        $str .= $ex->getFile() . '(' . $ex->getLine() . ")\n\n";

        return $str;
    }

    /**
     * @param \Throwable $ex
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function renderJson($ex)
    {
        $response = Json::renderWithFalse($ex->getMessage(), $this->getHttpStatusCode($ex));
        $response->headers->add(array('Access-Control-Allow-Origin' => '*'));
        return $response;
    }

    /**
     * http status code
     * @param \Throwable $ex
     * @return int
     */
    protected function getHttpStatusCode($ex)
    {
        if ($ex instanceof HttpException) {
            return $ex->getStatusCode();
        }

        if ($ex instanceof \PFinal\Routing\Exception\ResourceNotFoundException) {
            return 404;
        }

        if ($ex instanceof \PFinal\Routing\Exception\MethodNotAllowedException) {
            return 405;
        }

        if ($ex instanceof \PFinal\Database\NotFoundException) {
            return 400;
        }

        return 500;
    }

    protected function getReasonPhrase($httpStatusCode)
    {
        if (key_exists($httpStatusCode, \Leaf\Response::$statusTexts)) {
            return \Leaf\Response::$statusTexts[$httpStatusCode];
        }

        return 'Unknown Error';
    }

    protected function convert($size)
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
     * @param \Throwable $ex
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function renderHtml($ex)
    {
        if (!Application::$app['debug']) {

            // error404.twig, or error40x.twig, or error4xx.twig, or error.twig

            $code = $this->getHttpStatusCode($ex);
            $templates = array(
                'error' . $code . '.twig',
                'error' . substr($code, 0, 2) . 'x.twig',
                'error' . substr($code, 0, 1) . 'xx.twig',
                'error.twig',
            );

            /** @var \Twig_Environment $twig */
            $twig = Application::$app['twig'];
            $html = $twig->resolveTemplate($templates)->render(array(
                'code' => $code,
                'statusText' => $this->getReasonPhrase($code),
                'message' => $ex->getMessage(),
            ));

            return new \Leaf\Response($html, $code);
        }

        $type = get_class($ex);
        $message = $ex->getMessage();
        $file = $ex->getFile();
        $line = $ex->getLine();
        $trace = nl2br(htmlspecialchars($ex->getTraceAsString()));
        $mem = $this->convert(memory_get_usage(true));
        $time = @date('Y-m-d H:i:s');

        $html = <<<TAG
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
	<h2>$type $message</h2>
	<h2>$file($line)</h2>
</div>
<div class="main">{$trace}</div>
<div class="main">
	<h2>Memory Usage</h2>
	<div>{$mem}</div>
</div>
<div class="version">{$time}</div>
</body>
</html>
TAG;

        $httpStatus = $this->getHttpStatusCode($ex);

        return new Response($html, $httpStatus);
    }
}
