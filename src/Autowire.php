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

    private function reformatArgs($input): array
    {
        // Don't do anything if the array if empty
        if(count($input) === 0) return $input;

        $output = [];

        foreach($input as $name => $value){
            if(is_array($value) && array_key_exists('name', $value) && array_key_exists('value', $value)){
                $output[] = ['name' => trim($value['name'], " -"), 'value' => $value['value']];
            }else if(is_string($value)){
                $output[] = ['name' => trim($name, " -"), 'value' => $value];
            }else{
                throw new Exception("Unexpected argument format, don't know how to fix it");
            }
        }

        return $output;
    }

    public function getInstance(string $ref, ?array $args=[])
    {
        $rc = new \ReflectionClass($ref);
        $constructor = $rc->getConstructor();
        
        $args = $this->reformatArgs($args);
        $args = $this->resolveArgs($constructor, $args);

        return $rc->newInstanceArgs($args);
    }

    public function callMethod(object $class, string $method, ?array $args=[])
    {
        $rc = new \ReflectionClass($class);

        if($rc->hasMethod($method) === true || $rc->hasMethod('__call') === false){
            $rMethod = $rc->getMethod($method);

            $args = $this->reformatArgs($args);
            $args = $this->resolveArgs($rMethod, $args);

            return $rMethod->invoke($class, ...$args);
        }

        $rMethod = $rc->getMethod('__call');

        return $rMethod->invoke($class, $method, $args);
    }

    private function resolveArgs(\ReflectionFunctionAbstract $method, array $input): array
    {
        $signatureParameters = $method->getParameters();

        $output = [];

        // var_dump("STARTING AUTOWIRING....................");
        foreach($signatureParameters as $search){
            $name = $search->getName();
            $type = trim((string)$search->getType(), '?');
            // var_dump(['param' => $name, 'type' => $type]);

            // var_dump(['SEARCH PARAMETER' => [$name, $type]]);

            if(class_exists($type)){
                // When the type is a class, 
                foreach($input as $index => $data){
                    if($data['name']  === $name && is_object($data['value']) && get_class($data['value']) === $type){
                        $output[] = $data['value'];
                        unset($input[$index]);
                        // var_dump(['FOUND OBJECT ARG' => $name]);
                        continue 2;
                    }
                }

                $output[] = call_user_func($this->resolver, $type);
                // var_dump(["FOUND CONTAINER ARG" => $type]);
                continue;
            }else{
                // When the type is a string, we look in the input array for matches

                // for every named parameter, we must look for an input parameter with the same name AND HAS A VALUE
                foreach($input as $index => $data){
                    if($data['name'] === $name){
                        // var_dump(["TYPE CHECK($name)", is_numeric($data['value']) && is_int($data['value'] + 0), $data['value']]);
                        if($type === 'bool' && is_bool($data['value'])){
                            $output[] = (bool)$data['value'];
                            unset($input[$index]);
                            // var_dump(['FOUND NAMED BOOL' => $data]);
                            continue 2;
                        }else if($type === 'int' && is_numeric($data['value']) && is_int($data['value'] + 0)){
                            $output[] = (int)$data['value'];
                            unset($input[$index]);
                            // var_dump(['FOUND NAMED INT' => $data]);
                            continue 2;
                        }else if($type === 'float' && is_numeric($data['value']) && is_float($data['value'] + 0)){
                            $output[] = (float)$data['value'];
                            unset($input[$index]);
                            // var_dump(['FOUND NAMED FLOAT' => $data]);
                            continue 2;
                        }else if($type === 'string' && !empty($data['value'])){
                            $output[] = $data['value'];
                            unset($input[$index]);
                            // var_dump(['FOUND NAMED STRING' => $data]);
                            continue 2;
                        }
                    }
                }

                // We did not find a named parameter, therefore lets pick the first anonymous parameter
                foreach($input as $index => $data){
                    if(!empty($data['value'])){
                        continue;
                    }
                    // var_dump(["TYPE CHECK($type / {$data['name']})", 'numeric' => is_numeric($data['name']) && is_int($data['name'] + 0), 'value' => $data['name']]);
                    if($type === 'bool' && is_bool($data['name'])){
                        $output[] = (bool)$data['name'];
                        unset($input[$index]);
                        // var_dump(["FOUND ANON BOOL" => $data['name']]);
                        continue 2;
                    }else if($type === 'int' && is_numeric($data['name']) && is_int($data['name'] + 0)){
                        $output[] = (int)$data['name'];
                        unset($input[$index]);
                        // var_dump(["FOUND ANON INT" => $data['name']]);
                        continue 2;
                    }else if($type === 'float' && is_numeric($data['name']) && is_float($data['name'] + 0)){
                        $output[] = (float)$data['name'];
                        unset($input[$index]);
                        // var_dump(["FOUND ANON FLOAT" => $data['name']]);
                        continue 2;
                    }else if($type === 'string' && !empty($data['name'])){
                        $output[] = $data['name'];
                        unset($input[$index]);
                        // var_dump(["FOUND ANON STRING" => $data['name']]);
                        continue 2;
                    }
                }

                if ($search->isOptional()) {
                    // var_dump(["FOUND DEFAULT VALUE($name)" => $search->getDefaultValue()]);
                    $output[] = $search->getDefaultValue();
                    continue;
                }
            }

            throw new CannotAutowireParameterException($name, $type);
        }

        return $output;
    }
}