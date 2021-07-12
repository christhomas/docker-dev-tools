<?php declare(strict_types=1);

namespace DDT\Config;

use DDT\Docker\DockerRunProfile;

class DockerConfig
{
    private $key = 'docker';

    public function __construct(SystemConfig $config)
    {
        $this->config = $config;
    }

    public function listProfile(): array
    {
        return $this->config->getKey("$this->key.profile") ?? [];
    }

    public function readProfile(string $name): DockerRunProfile
    {
        return new DockerRunProfile($name);
    }

    public function writeProfile(DockerRunProfile $profile): bool
    {
        return false;
    }

    public function deleteProfile(string $name): bool
    {
        return false;
    }
}