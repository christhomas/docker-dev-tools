<?php declare(strict_types=1);

namespace DDT\Exceptions\Autowire;

class CannotAutowireParameterException extends \Exception {
    public function __construct(string $name, string $type, int $code = 0, \Throwable $previous = null){
        parent::__construct("Could not autowire parameter '$name' because type '$type' was not autowirable or with a default value", $code, $previous);
    }
}