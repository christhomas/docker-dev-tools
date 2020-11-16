<?php
class DockerMissingException extends Exception
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct("Docker is required to run this tool, please install it. $message", $code, $previous);
    }
}