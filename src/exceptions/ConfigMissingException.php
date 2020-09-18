<?php
class ConfigMissingException extends Exception{
    public function __construct(string $filename, int $code = 0, Throwable $previous=null)
    {
        parent::__construct("The Configuration '$filename' was missing", $code, $previous);
    }
}