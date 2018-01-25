<?php

namespace Leaf\Exception;

use Carbon\Carbon;

/**
 * 维护模式
 */
class MaintenanceModeException extends HttpException
{
    /**
     * 预计维护时长 (秒)
     * @var int
     */
    public $retryAfter;

    /**
     * 维护模式开始时间
     * @var \Carbon\Carbon
     */
    public $wentDownAt;

    /**
     * 计划维护结束时间
     * @var \Carbon\Carbon
     */
    public $willBeAvailableAt;

    /**
     * MaintenanceModeException constructor.
     * @param $time
     * @param null $retryAfter
     * @param null $message
     * @param \Exception $previous
     * @param int $code
     */
    public function __construct($time, $retryAfter = null, $message = null, $previous = null, $code = 0)
    {
        $this->wentDownAt = Carbon::createFromTimestamp($time);

        $headers = array();

        if ($retryAfter) {
            $headers = array('Retry-After' => $retryAfter);

            $this->retryAfter = $retryAfter;
            $this->willBeAvailableAt = Carbon::createFromTimestamp($time)->addSeconds($this->retryAfter);
        }

        parent::__construct(503, $message, $previous, $headers, $code);
    }
}
