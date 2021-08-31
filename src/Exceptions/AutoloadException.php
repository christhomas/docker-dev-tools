<?php declare(strict_types=1);

namespace DDT\Exceptions;

class AutoloadException extends \Exception
{
    public function __construct(string $fqcn, int $code = 0, \Throwable $previous = null)
    {
        parent::__construct("Could not autoload class name '$fqcn'", $code, $previous);
    }
}