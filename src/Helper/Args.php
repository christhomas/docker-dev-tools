<?php declare(strict_types=1);

namespace DDT\Helper;

class Args 
{
    private $args;

    public function __construct(array $args)
    {
        $this->args = new Arr($args);
    }

    public function has(string $name): bool
    {
        return false;
    }

    public function get(string $name): array
    {
        return [];
    }

    public function index(int $index): array
    {
        return [];
    }
}