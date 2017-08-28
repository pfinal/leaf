<?php

namespace Leaf\Facade;

use Leaf\Application;

class MailFacade
{
    /**
     * 发送邮件
     * @param string $to
     * @param string $title
     * @param string $content
     * @param null $error
     * @return bool
     */
    public static function send($to, $title, $content, &$error = null, $attach = array())
    {
        return Application::$app['mail']->send($to, $title, $content, $error, $attach);
    }
}