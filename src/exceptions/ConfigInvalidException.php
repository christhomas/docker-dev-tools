<?php
class ConfigInvalidException extends Exception{
    public function __construct(string $message, int $code = 0, Throwable $previous=null)
    {
        parent::__construct("The Configuration was invalid: $message", $code, $previous);
    }
}