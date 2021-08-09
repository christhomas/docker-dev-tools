<?php declare(strict_types=1);

namespace DDT\Exceptions\Tool;

class CommandInvalidException extends \Exception
{
    public function __construct(?string $message = null, $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message ?? "Given command data could not be understood", $code, $previous);
    }
}