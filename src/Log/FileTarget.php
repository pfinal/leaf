<?php

namespace Leaf\Log;

use Leaf\Application;
use Leaf\Json;

class FileTarget extends LogFilter
{
    public $maxFileSize = 10240; //KB

    public $maxLogFiles = 5;

    public $dirMode = 0775;

    protected $path;

    public function __construct($config = array())
    {
        parent::__construct($config);

        if ($this->maxLogFiles < 1) {
            $this->maxLogFiles = 1;
        }
        if ($this->maxFileSize < 1) {
            $this->maxFileSize = 1;
        }
    }

    protected function getLogFile($message)
    {
        if ($this->path == null) {
            $path = rtrim(Application::$app->getRuntimePath(), '/\\') . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR;
        } else {
            $path = rtrim($this->path, '/\\') . DIRECTORY_SEPARATOR;
        }

        if (!empty($message['channel'])) {
            $file = $path . strtolower($message['channel']) . '.log';
        } else {
            $file = $path . strtolower($message['level']) . '.log';
        }

        if (!file_exists(dirname($file))) {
            if (!@mkdir(dirname($file), $this->dirMode, true)) {
                throw new \Exception("Create directory error: " . dirname($file));
            }
        }
        return $file;
    }

    public function export()
    {
        foreach ($this->messages as $message) {
            $this->save($message);
        }
    }

    protected function save($log)
    {
        $logFile = $this->getLogFile($log);

        if (($fp = @fopen($logFile, 'a')) === false) {
            throw new \Exception("Unable to append to log file: {$logFile}");
        }

        @flock($fp, LOCK_EX);
        clearstatcache();

        $text = '[' . $log['datetime'] . "]\n" . $log['message'] . "\n" . Json::encode($log['context']) . "\n\n";

        if (@filesize($logFile) > $this->maxFileSize * 1024) {
            $this->rotateFiles($logFile);
            @flock($fp, LOCK_UN);
            @fclose($fp);
            @file_put_contents($logFile, $text, FILE_APPEND | LOCK_EX);
        } else {
            @fwrite($fp, $text);
            @flock($fp, LOCK_UN);
            @fclose($fp);
        }
    }

    protected function rotateFiles($file)
    {
        for ($i = $this->maxLogFiles; $i >= 0; --$i) {
            // $i == 0 is the original log file
            $rotateFile = $file . ($i === 0 ? '' : '.' . $i);
            if (is_file($rotateFile)) {
                // suppress errors because it's possible multiple processes enter into this section
                if ($i === $this->maxLogFiles) {
                    @unlink($rotateFile);
                } else {
                    @rename($rotateFile, $file . '.' . ($i + 1));
                }
            }
        }
    }
}
