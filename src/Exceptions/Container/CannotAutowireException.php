<?php declare(strict_types=1);

namespace DDT\Exceptions\Container;

class CannotAutowireException extends \Exception {
    public function __construct(string $name, string $ref, string $type, int $code = 0, \Throwable $previous = null){
        parent::__construct("Could not autowire parameter '$name' on ref '$ref' because type '$type' was not autowirable or with a default value", $code, $previous);
    }
}