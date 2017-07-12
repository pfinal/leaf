<?php

namespace Leaf\Log;

use Leaf\DB;

/**
 *
 * $app->register(new \Leaf\Provider\LogServiceProvider(),['log.config'=>['class'=>'Leaf\Log\DbTarget']]);
 *
 * DROP TABLE IF EXISTS pre_log;
 *
 * DROP TABLE IF EXISTS pre_log;
 * CREATE TABLE `pre_log` (
 *   `id` bigint NOT NULL AUTO_INCREMENT,
 *   `channel` varchar(255) DEFAULT '',
 *   `level` varchar(255) DEFAULT '',
 *    `message` text,
 *   `context` text,
 *   `datetime` datetime DEFAULT NULL,
 *   PRIMARY KEY (`id`)
 */
class DbTarget extends LogFilter
{
    protected $table = '{{%log}}';

    public function export()
    {
        foreach ($this->messages as $message) {
            $message['context'] = static::varToString($message['context']);
            DB::table($this->table)->insert($message);
        }
    }
}
