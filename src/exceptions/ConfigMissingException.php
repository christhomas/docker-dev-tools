<?php
class ConfigMissingException extends Exception{
    public function __construct(string $filename, int $code = 0, Throwable $previous=null)
    {
        parent::__construct("The Configuration file named '$filename' or a file inside path '$filename' could not be found", $code, $previous);
    }
}