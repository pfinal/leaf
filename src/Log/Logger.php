<?php

namespace Leaf\Log;

use Leaf\Application;
use Monolog\Handler\RotatingFileHandler;

class Logger extends \Monolog\Logger
{
    public function __construct($name, $handlers = array(), $processors = array())
    {
        parent::__construct($name, $handlers, $processors);

        $name = preg_replace('/[^\w]/', '_', $name);

        $file = Application::$app->getRuntimePath() . '/logs/' . $name . '.log';

        $sh = new RotatingFileHandler($file, 30);

        $sh->setFormatter(new MultiLineFormatter());

        $this->pushHandler($sh);

        $this->pushProcessor(new \Monolog\Processor\WebProcessor());
        $this->pushProcessor(new \Monolog\Processor\IntrospectionProcessor(Logger::DEBUG, ['Leaf\\Facade\\LogFacade']));
    }
}