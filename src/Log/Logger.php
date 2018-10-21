<?php

namespace Leaf\Log;

use Leaf\Application;
use Monolog\Formatter\LineFormatter;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\RotatingFileHandler;


/**
 * 写JSON格式
 * $app->register(new \Leaf\Provider\LogServiceProvider(), ['log.config' => ['formatter' => new \Monolog\Formatter\JsonFormatter()]]);
 *
 * 直接写到ElasticSearch
 * //http://elastica.io
 * // composer require ruflin/elastica
 * $client = new \Elastica\Client(['servers' => [
 *     ['host' => '192.168.88.162', 'port' => 9200],
 * ]]);
 *
 * $options = array(
 *     'index' => 'testlog-' . @date('Y-m-d'),
 *     'type' => 'testlog',
 * );
 * $handler = new ElasticSearchHandler($client, $options);
 *
 * $app->register(new \Leaf\Provider\LogServiceProvider(), ['log.config' => ['handlers' => [$handler]]]);
 */
class Logger extends \Monolog\Logger
{
    public function __construct($config = array())
    {
        $app = Application::$app;
        $config = $config + [
                'name' => $app['name'], //channel
                'level' => $app['debug'] ? Logger::DEBUG : Logger::INFO,
                'formatter' => new Formatter(), //Leaf\Log\Formatter
            ];

        $logPath = $app->getRuntimePath() . '/logs/';
        $filename = $config['name'] . '.log';

        //$formatter = new \Monolog\Formatter\JsonFormatter();

        // $formatter = new LineFormatter();
        // $formatter->includeStacktraces();

        $handler = new RotatingFileHandler($logPath . $filename, 30, $config['level']);
        $handler->setFormatter($config['formatter']);

        $config = $config + [
                'handlers' => array($handler),
                'processors' => array(
                    new \Monolog\Processor\WebProcessor(),
                    new \Monolog\Processor\IntrospectionProcessor($config['level'], ['Leaf\\Facade\\LogFacade'])
                ),
            ];

        foreach ($config as $key => $value) {
            $this->{$key} = $value;
        }

    }
}