<?php

namespace Leaf;

/**
 * 错误和异常处理
 */
class ErrorHandler
{
    /**
     * 转换错误为异常
     * @throws \ErrorException
     */
    public function handleError($errno, $errstr, $errfile, $errline)
    {
        if (!(error_reporting() & $errno)) { //避免@失效
            return;
        }

        //restore_error_handler();

        throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
    }

    /**
     * 处理异常
     *
     * @param \Throwable $ex
     */
    public function handleException($ex)
    {
        restore_exception_handler();

        $app = Application::$app;

        if ($app->has('Leaf\ErrorResponseGenerator')) {
            $generator = Application::$app->get('Leaf\ErrorResponseGenerator');
        } else {
            $generator = new ErrorResponseGenerator();
        }

        $response = $generator($ex, $app->has('request') ? $app->get('request') : null);

        if ($response instanceof \Symfony\Component\HttpFoundation\Response) {
            $response->send();
        } else {
            echo (string)$response;
        }

        exit;
    }
}
