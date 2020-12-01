<?php
class UnsupportedDistroException extends Exception
{
    private $operatingSystem;

    public function __construct(string $message, int $code = 0, Throwable $previous = null)
    {
        parent::__construct("This operating system was not supported, installed: '$message'", $code, $previous);

        $this->operatingSystem = $message;
    }

    public function getOperatingSystem(): string
    {
        return $this->operatingSystem;
    }
}