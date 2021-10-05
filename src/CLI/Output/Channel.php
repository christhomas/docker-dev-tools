<?php declare(strict_types=1);

namespace DDT\CLI\Output;

class Channel
{
    private $name;
    
    public function __construct(string $name)
    {
        $this->name = $name;
    }
}