<?php declare(strict_types=1);

namespace DDT\Exceptions\CLI;

use Exception;
use Throwable;

class ExecException extends Exception
{
    private $stdout;
    private $stderr;

    public function __construct(string $stdout, string $stderr, int $code, ?Throwable $previous=null)
    {
        parent::__construct("$stdout\n$stderr", $code, $previous);
        $this->stdout = $stdout;
        $this->stderr = $stderr;
    }

    public function getStdout(): string
    {
        return $this->stdout;
    }

    public function getStderr(): string
    {
        return $this->stderr;
    }
}