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
     * @return bool
     */
    public static function send($to, $title, $content)
    {
        return Application::$app['mail']->send($to, $title, $content);
    }

}