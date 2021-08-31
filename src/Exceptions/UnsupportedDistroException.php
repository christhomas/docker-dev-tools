<?php declare(strict_types=1);

namespace DDT\Exceptions;

class UnsupportedDistroException extends \Exception
{
    private $operatingSystem;

    public function __construct(string $installed, int $code = 0, \Throwable $previous = null)
    {
        parent::__construct("This distro or operating system was not supported, installed: '$installed'", $code, $previous);

        $this->operatingSystem = $installed;
    }

    public function getOperatingSystem(): string
    {
        return $this->operatingSystem;
    }
}