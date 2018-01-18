<?php

namespace Leaf\Exception;

class HttpException extends \RuntimeException
{
    protected $statusCode;
    protected $headers;

    /**
     * HttpException constructor.
     * @param $statusCode
     * @param null $message
     * @param \Throwable $previous
     * @param array $headers
     * @param int $code
     */
    public function __construct($statusCode, $message = null, $previous = null, array $headers = array(), $code = 0)
    {
        $this->statusCode = $statusCode;
        $this->headers = $headers;

        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function getHeaders()
    {
        return $this->headers;
    }
}
