<?php declare(strict_types=1);

namespace DDT\Docker;

class DockerContainer
{
    private $docker;

    public function __construct(Docker $docker)
    {
        $this->docker = $docker;
    }

    public function stop(): bool
    {
        return false;
    }
}