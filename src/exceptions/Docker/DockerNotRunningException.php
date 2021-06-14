<?php declare(strict_types=1);

namespace DDT\Exceptions\Docker;

class DockerNotRunningException extends \Exception
{
    public function __construct($message = "", $code = 0, \Throwable $previous = null)
    {
        parent::__construct("Docker is not running. $message", $code, $previous);
    }
}