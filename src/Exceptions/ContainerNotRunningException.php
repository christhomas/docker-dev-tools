<?php
class ContainerNotRunningException extends Exception
{
    public function __construct(string $message, int $code = 0, Throwable $previous = null)
    {
        parent::__construct("Container '$message' was not running", $code, $previous);
    }
}