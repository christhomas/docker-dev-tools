<?php declare(strict_types=1);
class ConfigWrongTypeException extends Exception
{
    public function __construct(array $type, int $code = 0, Throwable $previous=null)
    {
        parent::__construct("The Configuration was type '{$type[0]}' but should have been type '{$type[1]}'", $code, $previous);
    }
}