<?php declare(strict_types=1);

namespace DDT\Exceptions\Container;

class ContainerNotInstantiatedException extends \Exception {
    public function __construct(int $code = 0, \Throwable $previous = null){
        parent::__construct("You must create the container before attempting to use it", $code, $previous);
    }
}