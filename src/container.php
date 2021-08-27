<?php declare(strict_types=1);

namespace DDT;

use DDT\Exceptions\Container\CannotAutowireException;
use DDT\Exceptions\Container\NotClassReferenceException;

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

        $reflection = new \ReflectionClass($ref);
        $constructor = $reflection->getConstructor();        
        $parameters = $constructor ? $constructor->getParameters() : [];
    
        $finalArgs = [];
        
        foreach($parameters as $p){
            $name = $p->getName();
            $type = (string)$p->getType();
            // var_dump(['ref' => $ref, 'param' => $name, 'type' => $type]);

            if(array_key_exists($name, $args)){
                $finalArgs[] = $args[$name];
            }else{
                // var_dump(['class-exists' => [$type, class_exists($type)]]);
                if($this->has($type)){
                    $finalArgs[] = $this->get($type);
                }else if ($p->isOptional()) {
                    $finalArgs[] = $p->getDefaultValue();
                }else{
                    throw new CannotAutowireException($name, $ref, $type);
                }
            }
        }

        //  This is too much output to leave in by default, we need to support debug=verbose or something 
        //  cause this just overloads the programmer with far too much information to be useful in the general case
        //\Text::print("{debug}{red}[CONTAINER]:{end} final-args: ".serialize($finalArgs)."\n{/debug}");
    
        return $reflection->newInstanceArgs($finalArgs);
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
