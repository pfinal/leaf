<?php

namespace Leaf\Log;

use Leaf\DB;

/**
 *
 *  CREATE TABLE `pre_log` (
 *  `id` INT(11) NOT NULL AUTO_INCREMENT,
 *  `level` VARCHAR(256) NULL DEFAULT NULL,
 *  `category` VARCHAR(128) NULL DEFAULT NULL,
 *  `message` TEXT NULL,
 *  `created_at` INT(11) UNSIGNED NULL DEFAULT NULL,
 *  PRIMARY KEY (`id`)
 *  ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
 *
 * @package Rain\Log
 */
class DbTarget
{
    public $messages = [];
    /**
     * @var string name of the DB table to store cache content. Defaults to "log".
     */
    public $logTable = '{{%log}}';

    /**
     * Stores log messages to DB.
     */
    public function export()
    {
        $tableName = $this->logTable;
        $sql = "INSERT INTO $tableName ([[level]], [[category]], [[created_at]], [[message]])
                VALUES (:level, :category, :created_at, :message)";

        foreach ($this->messages as $message) {
            list($text, $level, $category, $timestamp) = $message;

            if (!is_string($text)) {
                $text = Log::varToString($text);
            }

            DB::getConnection()->execute($sql, [
                'level' => $level,
                'category' => $category,
                'created_at' => $timestamp,
                'message' => $text,
            ]);
        }
    }
}
