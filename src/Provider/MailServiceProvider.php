<?php

namespace Leaf\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Leaf\Application;
use Leaf\Log;

/**
 * 发送邮件
 *
 * composer require swiftmailer/swiftmailer:5.*
 *
 * $app->register(new \Leaf\Provider\MailServiceProvider(), ['mail.config'=>[
 *   'host' => 'smtp.qq.com',
 *   'username' => '123456@qq.com',
 *   'password' => 'xxx',
 *   'name' => 'your name',
 *   'port' => '465',
 *   'encryption' => 'ssl', //ssl、tls
 * ]]);
 *
 * @author  Zou Yiliang
 */
class MailServiceProvider implements ServiceProviderInterface
{
    protected $host;
    protected $port;
    protected $username;
    protected $password;
    protected $encryption;

    protected $name;

    public function __construct($config = array())
    {
        foreach ($config as $key => $value) {
            $this->$key = $value;
        }
    }

    /**
     * 在容器中注册服务
     *
     * @param Container $app
     */
    public function register(Container $app)
    {
        $app['mail'] = function () use ($app) {
            $config = isset($app['mail.config']) ? $app['mail.config'] : array();
            $config += array('class' => 'Leaf\Provider\MailServiceProvider');
            $class = $config['class'];
            unset($config['class']);
            return $app->make($class, array('config' => $config));
        };
    }

    /**
     * 发送邮件
     * @param string $to
     * @param string $title
     * @param string $content
     * @param null $error
     * @return bool
     */
    public function send($to, $title, $content, &$error = null)
    {
        try {
            // message
            $message = \Swift_Message::newInstance();
            $message->setFrom(array($this->username => $this->name));
            $message->setTo($to);
            $message->setSubject($title);
            $message->setBody($content, 'text/html', 'utf-8');
            //$message->attach(\Swift_Attachment::fromPath('pic.jpg', 'image/jpeg')->setFilename('rename_pic.jpg'));

            //transport
            $transport = \Swift_SmtpTransport::newInstance($this->host, $this->port);
            $transport->setUsername($this->username);
            $transport->setPassword($this->password);
            if ($this->encryption != null) {
                $transport->setEncryption($this->encryption);
            }

            //mailer
            $mailer = \Swift_Mailer::newInstance($transport);

            $mailer->send($message);
            return true;
        } catch (\Exception $e) {
            $error = $e->getMessage();
            Log::error($error);
            return false;
        }
    }
}