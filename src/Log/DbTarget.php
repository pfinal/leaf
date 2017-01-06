<?php

namespace Leaf\Log;

use Leaf\DB;

class DbTarget extends LogFilter
{
    protected $table = '{{%log}}';

    public function export()
    {
        //array('channel' => $channel, 'level' => $level, 'message' => $log, 'datetime' => $time, 'context' => $context)
        foreach ($this->messages as $message) {
            $message['context'] = static::varToString($message['context']);
            DB::table($this->table)->insert($message);
        }
    }
}
