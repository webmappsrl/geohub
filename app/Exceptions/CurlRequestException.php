<?php

namespace App\Exceptions;

use Exception;

/**
 * Thrown by CurlServiceProvider::exec(): keeps the HTTP status and the cURL error number
 * so callers can tell a transient failure (429, 5xx, timeout) from one that retrying cannot fix
 * (406, 403, connection refused).
 */
class CurlRequestException extends Exception
{
    /** @var int HTTP status code, 0 when the request did not get a response */
    private $httpStatus;

    /** @var int cURL error number, 0 when the transfer itself succeeded */
    private $curlErrno;

    public function __construct(string $message, int $httpStatus = 0, int $curlErrno = 0)
    {
        parent::__construct($message, $httpStatus);
        $this->httpStatus = $httpStatus;
        $this->curlErrno = $curlErrno;
    }

    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }

    public function getCurlErrno(): int
    {
        return $this->curlErrno;
    }
}
