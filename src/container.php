<?php declare(strict_types=1);

namespace DDT;

use DDT\Exceptions\Container\CannotAutowireException;
use DDT\Exceptions\Container\CannotAutowireParameterException;
use DDT\Exceptions\Container\NotClassReferenceException;
use ReflectionFunctionAbstract;

class Container {
    static public $instance = null;

    private $bind = [];
    private $singleton = [];
    private $singletonCache = [];

    public function __construct()
    {
        self::$instance = $this;
    }

    public function bind(string $ref, $func){
        $this->bind[$ref] = $func;
    }

    public function singleton(string $ref, $func){
        $this->singleton[$ref] = $func;
        unset($this->singletonCache[$ref]);
    }

    private function createInstance(string $ref, array $args = []) {
        \Text::print("{debug}{red}[CONTAINER]:{end} '$ref' was bound as an instance\n{/debug}");

        $thing = $this->bind[$ref];

        switch(true){
            case is_callable($thing):
                return call_user_func_array($thing, $args);

            case class_exists($thing):
                return $this->createClass($thing, $args);
        }
    }

    private function createSingleton(string $ref, array $args = []) {
        \Text::print("{debug}{red}[CONTAINER]:{end} '$ref' was bound as a singleton\n{/debug}");

        if(!array_key_exists($ref, $this->singletonCache)){

            $thing = $this->singleton[$ref];

            // var_dump($thing);

            switch(true){
                case is_callable($thing):
                    \Text::print("{debug}{red}[CONTAINER]:{end} the singleton references a callable\n{/debug}");
                    $this->singletonCache[$ref] = call_user_func_array($thing, $args);
                    break;

                case is_object($thing):
                    \Text::print("{debug}{red}[CONTAINER]:{end} the singleton references an object\n{/debug}");
                    $this->singletonCache[$ref] = $thing;
                    break;

                case class_exists($thing):
                    \Text::print("{debug}{red}[CONTAINER]:{end} the singleton references a class name\n{/debug}");
                    $this->singletonCache[$ref] = $this->createClass($thing, $args);
                    break;

                case is_scalar($thing):
                    \Text::print("{debug}{red}[CONTAINER]:{end} the singleton references a scalar value\n{/debug}");
                    $this->singletonCache[$ref] = $thing;
                    break;    
            }
        }

        return $this->singletonCache[$ref];
    }

    private function createClass(string $ref, array $args = []) {
        \Text::print("{debug}{red}[CONTAINER]:{end} '$ref' was bound as class\n{/debug}");
        
        // NOTE: You can't use the container to get this class, infinite loop!
        $autowire = new Autowire($this);
        
        return $autowire->getInstance($ref, $args);
    }

    public function isSingleton(string $ref): bool {
        return array_key_exists($ref, $this->singleton);
    }

    public function isInstance(string $ref): bool{
        return array_key_exists($ref, $this->bind);
    }

    public function isClass(string $ref): bool {
        return class_exists($ref);
    }

    public function has(string $ref): bool {
        if($this->isSingleton($ref)) return true;
        if($this->isInstance($ref)) return true;
        if($this->isClass($ref)) return true;
        
        return false;
    }

    public function get(?string $ref = null, ?array $args = [])
    {
        switch(true){
            case $this->isSingleton($ref):
                return $this->createSingleton($ref, $args);
            case $this->isInstance($ref):
                return $this->createInstance($ref, $args);
            case $this->isClass($ref):
                return $this->createClass($ref, $args);
            default:
                throw new NotClassReferenceException($ref);
        }
    }
}
