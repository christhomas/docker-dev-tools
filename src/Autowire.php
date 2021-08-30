<?php declare(strict_types=1);

namespace DDT;

use DDT\Exceptions\Container\CannotAutowireParameterException;

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

        //  This is too much output to leave in by default, we need to support debug=verbose or something 
        //  cause this just overloads the programmer with far too much information to be useful in the general case
        //\Text::print("{debug}{red}[CONTAINER]:{end} final-args: ".serialize($finalArgs)."\n{/debug}");
        
        return $reflectionClass->newInstanceArgs($finalArgs);
    }

    public function callMethod(object $class, string $method, ?array $args=[])
    {
        // obtain using reflect all the method parameters
        $reflectionMethod = new \ReflectionMethod($class, $method);

        $finalArgs = $this->getMethodArgsFromCLI($reflectionMethod, $args);

        return $reflectionMethod->invoke($class, ...$finalArgs);
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
                    unset($args[$key]);
                    $a = $item;
                }
            }

            // named arguments can't be found, then this is an error
            if(empty($a)){
                throw new \Exception("This command required a parameter --{$name}, see help for more information");
            }

            // cast the value to the correct type according to reflection
            $v = null;
            $v = $a['value'];
            settype($v, $type);

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