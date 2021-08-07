<?php declare(strict_types=1);

use DDT\Exceptions\Container\CannotAutowireException;
use DDT\Exceptions\Container\NotClassReferenceException;

class Container {
    static public $instance = null;

    private $bind = [];
    private $singleton = [];

    public function __construct()
    {
        self::$instance = $this;
    }

    public function bind(string $ref, callable $func){
        $this->bind[$ref] = $func;
    }

    public function singleton(string $ref, callable $func){
        $this->singleton[$ref] = $func;
    }

    private function createInstance(string $ref, array $args = []) {
        // var_dump("'$ref' was instance bound");
        $instance = call_user_func_array($this->bind[$ref], $args);

        return $instance;
    }

    private function createSingleton(string $ref, array $args = []) {
        static $instance = [];
        // var_dump("'$ref' was singleton");

        if(!array_key_exists($ref, $instance)){
            // var_dump("'$ref' is callable");
            $instance[$ref] = call_user_func_array($this->singleton[$ref], $args);
        }

        return $instance[$ref];
    }

    private function createClass(string $ref, array $args = []) {
        // var_dump("'$ref' was reflected class");
        $reflection = new \ReflectionClass($ref);
        $constructor = $reflection->getConstructor();
        $parameters = $constructor->getParameters();
    
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

        // var_dump(['final-args' => $finalArgs]);
    
        return $reflection->newInstanceArgs($finalArgs);
    }

    public function isInstance(string $ref): bool{
        return array_key_exists($ref, $this->bind);
    }

    public function isSingleton(string $ref): bool {
        return array_key_exists($ref, $this->singleton);
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

    public function get(string $ref, array $args = [])
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

function container(string $ref, array $args = []){
    static $container = null;

    if($container === null){
        $container = Container::$instance ?? new Container();
    }

    return $container->get($ref, $args);
}