<?php
class ProjectManager
{
    private $config;

    public function __construct(SystemConfig $config)
    {
        $this->config = $config;
    }

    public function install(string $project, string $url): bool
    {
        $repo = new Git();
        $repo->exists($this->config->getToolsPath());
        return false;
    }

    public function uninstall(string $project): bool
    {
        return false;
    }

    public function list(): array
    {
        return [];
    }
}