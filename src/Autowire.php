<?php declare(strict_types=1);

namespace DDT;

use DDT\Exceptions\Container\CannotAutowireParameterException;
use ReflectionClass;

class Autowire
{
    /** @var Container */
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
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
        $rClass= new \ReflectionClass($class);

        if($rClass->hasMethod($method) === true || $rClass->hasMethod('__call') === false){
            $rMethod = $rClass->getMethod($method);
            $finalArgs = $this->getMethodArgsFromCLI($rMethod, $args);

            return $rMethod->invoke($class, ...$finalArgs);
        }

        $rMethod = $rClass->getMethod('__call');

        return $rMethod->invoke($class, $method, $args);
    }

    public function getMethodArgsFromAssocArray(?\ReflectionFunctionAbstract $method = null, array $input): array
    {
        $parameters = $method ? $method->getParameters() : [];
    
        $output = [];
        
        foreach($parameters as $p){
            $name = $p->getName();
            $type = (string)$p->getType();
            // var_dump(['param' => $name, 'type' => $type]);

            if(array_key_exists($name, $input)){
                $output[] = $input[$name];
            }else{
                // var_dump(['class-exists' => [$type, class_exists($type)]]);
                if($this->container->has($type)){
                    $output[] = $this->container->get($type);
                }else if ($p->isOptional()) {
                    $output[] = $p->getDefaultValue();
                }else{
                    throw new CannotAutowireParameterException($name, $type);
                }
            }
        }

        return $output;
    }

    public function getMethodArgsFromCLI(?\ReflectionFunctionAbstract $method = null, array $input): array
    {
        // the method might not have any arguments, default to empty list
        $parameters = $method->getParameters() ?? [];

        $output = [];

        // loop through them to pull out the information from the cli
        foreach($parameters as $p){
            $name = $p->getName();
            $type = (string)$p->getType();

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
            }

            if(empty($v)){
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