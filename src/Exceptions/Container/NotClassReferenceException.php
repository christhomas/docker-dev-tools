<?php declare(strict_types=1);

namespace DDT\Exceptions\Container;

class NotClassReferenceException extends \Exception{
    public function __construct(string $ref, int $code = 0, \Throwable $previous = null)
    {
        parent::__construct("Reference requested for '$ref' but this is not a class", $code, $previous);
    }
}