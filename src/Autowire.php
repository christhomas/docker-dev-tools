<?php declare(strict_types=1);

namespace DDT;

use Exception;
use DDT\Exceptions\Autowire\CannotAutowireParameterException;

class Autowire
{
    /** @var callable $resolver A callback to return a type that the autowire class wants resolved to an argument to pass */
    private $resolver;

    public function __construct(callable $resolver)
    {
        $this->resolver = $resolver;
    }

    static public function instantiator(callable $resolver, string $ref, array $args)
    {
        $autowire = new Autowire($resolver);

        // Special case for the autowire class
        if($ref === Autowire::class){
            return $autowire;
        }
        
        return $autowire->getInstance($ref, $args);
    }

    public function getInstance(string $ref, ?array $args=[])
    {
        $reflectionClass = new \ReflectionClass($ref);
        $constructor = $reflectionClass->getConstructor();
        $finalArgs = $this->getMethodArgsFromAssocArray($constructor, $args);

        return $reflectionClass->newInstanceArgs($finalArgs);
    }

    public function callMethod(object $class, string $method, ?array $args=[])
    {
        $classMetadata = new \ReflectionClass($class);

        if($classMetadata->hasMethod($method) === true || $classMetadata->hasMethod('__call') === false){
            $rMethod = $classMetadata->getMethod($method);
            $finalArgs = $this->getMethodArgsFromCLI($rMethod, $args);

            return $rMethod->invoke($class, ...$finalArgs);
        }

        $rMethod = $classMetadata->getMethod('__call');

        return $rMethod->invoke($class, $method, $args);
    }

    public function getMethodArgsFromAssocArray(\ReflectionFunctionAbstract $method, array $input): array
    {
        $parameters = $method->getParameters();
    
        $output = [];
        
        foreach($parameters as $p){
            $name = $p->getName();
            $type = trim((string)$p->getType(), '?');
            // var_dump(['param' => $name, 'type' => $type]);

            if(array_key_exists($name, $input)){
                $output[] = $input[$name];
            }else{
                // var_dump(['class-exists' => [$type, class_exists($type)]]);

                try{
                    $instance = call_user_func($this->resolver, $type);
                }catch(Exception $e){
                    $instance = null;
                }

                if($instance){
                    $output[] = $instance;
                }else if ($p->isOptional()) {
                    $output[] = $p->getDefaultValue();
                }else{
                    throw new CannotAutowireParameterException($name, $type);
                }
            }
        }

        return $output;
    }

    public function getMethodArgsFromCLI(\ReflectionFunctionAbstract $method, array $input): array
    {
        // the method might not have any arguments, default to empty list
        $parameters = $method->getParameters();

        $output = [];

        // loop through them to pull out the information from the cli
        foreach($parameters as $p){
            $name = $p->getName();
            $type = trim((string)$p->getType(), '?');

            // all named arguments are prefixed with double dash
            $a = null;
            foreach($input as $key => $item){
                if($item['name'] === "--{$name}"){
                    unset($input[$key]);
                    $a = $item;
                }
            }

            // named arguments can't be found, then this is an error
            if(empty($a)){
                if($p->isOptional()){
                    $a = ['name' => $name, 'value' => $p->getDefaultValue()];
                }else{
                    throw new \Exception("This command required a parameter --{$name}, see help for more information");
                }
            }

            // cast the value to the correct type according to reflection
            $v = null;
            $v = $a['value'];
            if(!empty($type)){
                settype($v, $type);
                $v = (string)$a['value'] == (string)$v ? $v : null;
            }

            if(strlen((string)$v) === 0){
                if($p->isOptional()){
                    // if empty, and optional, use defaultValue();
                    $v = $p->getDefaultValue();
                }else{
                    // if empty, but not optional, throw exception, this is an error
                    throw new \Exception("The parameter --{$name} is not optional, has no default value, and must be provided");
                }
            }

            $output[] = $v;
        }

        return $output;
    }
}