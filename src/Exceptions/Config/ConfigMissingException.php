<?php declare(strict_types=1);

namespace DDT\Exceptions\Config;

class ConfigMissingException extends \Exception
{
    public function __construct(string $filename, int $code = 0, \Throwable $previous=null)
    {
        parent::__construct("The Configuration file was not found or unreadable: '$filename'", $code, $previous);
    }
}