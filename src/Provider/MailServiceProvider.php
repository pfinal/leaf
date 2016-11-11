<?php

namespace Leaf\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Leaf\Application;
use Leaf\Log;

/**
 * 发送邮件
 *
 * $app->register(new \Leaf\Provider\MailServiceProvider(), [
 *       'mail.host' => 'smtp.qq.com',
 *       'mail.username' => '190196539@qq.com',
 *       'mail.password' => 'xxx',
 *       'mail.name' => 'Ethan',
 *       'mail.port' => '465',
 *       'mail.encryption' => 'ssl', //ssl、tls
 * ]);
 *
 * @author  Zou Yiliang
 */
class MailServiceProvider implements ServiceProviderInterface
{
    /**
     * 在容器中注册服务
     *
     * @param Container $app
     */
    public function register(Container $app)
    {
        $app['mail'] = function () {
            return new self;
        };
    }

    public function send($to, $title, $content)
    {
        $config['host'] = Application::$app['mail.host'];
        $config['port'] = Application::$app['mail.port'];
        $config['username'] = Application::$app['mail.username'];
        $config['password'] = Application::$app['mail.password'];
        $config['encryption'] = Application::$app['mail.encryption'];

        $config['name'] = Application::$app['mail.name'];

        try {
            // message
            $message = \Swift_Message::newInstance();
            $message->setFrom(array($config['username'] => $config['name']));
            $message->setTo($to);
            $message->setSubject($title);
            $message->setBody($content, 'text/html', 'utf-8');
            //$message->attach(\Swift_Attachment::fromPath('pic.jpg', 'image/jpeg')->setFilename('rename_pic.jpg'));

            //transport
            $transport = \Swift_SmtpTransport::newInstance($config['host'], $config['port']);
            $transport->setUsername($config['username']);
            $transport->setPassword($config['password']);
            if (isset($config['encryption'])) {
                $transport->setEncryption($config['encryption']);
            }

            //mailer
            $mailer = \Swift_Mailer::newInstance($transport);

            $mailer->send($message);
            return true;
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return false;
        }
    }
}