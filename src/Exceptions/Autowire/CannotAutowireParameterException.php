<?php declare(strict_types=1);

namespace DDT\Exceptions\Autowire;

class CannotAutowireParameterException extends \Exception {
    private $className;
    private $methodName;
    private $parameterName;
    private $parameterType;

    public function __construct(string $name, string $type, int $code = 0, \Throwable $previous = null){
        parent::__construct("Could not autowire parameter '$name' because type '$type' was not autowirable or with a default value", $code, $previous);
        $this->parameterName = $name;
        $this->parameterType = $type;
    }

    public function setClassName(string $className): void
    {
        $this->className = $className;
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function setMethodName(string $methodName): void
    {
        $this->methodName = $methodName;
    }

    public function getMethodName(): string
    {
        return $this->methodName;
    }

    public function getParameterName(): string
    {
        return $this->parameterName;
    }

    public function getParameterType(): string
    {
        return $this->parameterType;
    }
}