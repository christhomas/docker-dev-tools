<?php declare(strict_types=1);

namespace DDT\Exceptions\CLI;

use Exception;
use Throwable;

class PassthruException extends Exception
{
    private $command;

    public function __constuct(string $command, int $code, Throwable $previous)
    {
        parent::__construct($command, $code, $previous);
        $this->command = $command;
    }

    public function getCommand(): string
    {
        return $this->command;
    }
}