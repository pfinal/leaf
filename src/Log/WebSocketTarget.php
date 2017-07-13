<?php

namespace Leaf\Log;

use Leaf\Json;

/**
 * composer require textalk/websocket
 *
 * $app->register(new \Leaf\Provider\LogServiceProvider(), ['log.config' => [
 *      'class' => 'Leaf\Log\WebSocketTarget',
 *      'server' => 'ws://127.0.0.1:8081',
 * ]]);
 */
class WebSocketTarget extends LogFilter
{
    protected $server = 'ws://127.0.0.1:8081';

    public function export()
    {
        try {
            $client = new \WebSocket\Client($this->server);

            foreach ($this->messages as $message) {
                $message['context'] = static::varToString($message['context']);

                $data = ['api' => 'push-log', 'content' => Json::encode($message)];

                $client->send(Json::encode($data));
            }
        } catch (\Exception $ex) {
            //echo $ex->getMessage();
        }

    }
}
