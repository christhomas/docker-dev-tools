<?php declare(strict_types=1);

namespace DDT\Exceptions\Config;

class ConfigReadonlyException extends \Exception
{
    public function __construct(
        string $message = "The Configuration is readonly", 
        int $code = 0, 
        \Throwable $previous=null
    ) {
        parent::__construct($message, $code, $previous);
    }
}