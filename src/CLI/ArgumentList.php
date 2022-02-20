<?php declare(strict_types=1);

namespace DDT\CLI;

class ArgumentList
{
    /** @var array */
    private $argList;

    public function __construct(array $argList, ?int $start=0, ?int $length=null)
    {
        $this->argList = $argList;
        $this->slice($start, $length);
    }

    public function slice(int $start, ?int $length=null): array
    {
        return $this->argList = array_slice($this->argList, $start, $length);
    }

    public function __toString(): string
    {
        return implode(" ", array_map(function($a){
            if(!array_key_exists('value', $a)){
                $a['value'] = '';
            }
            return trim("{$a['name']}={$a['value']}", " =");
        }, $this->argList));
    }

    public function get(): array
    {
        return $this->argList;
    }
}