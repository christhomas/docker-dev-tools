<?php declare(strict_types=1);

namespace DDT\Docker;

use DDT\Config\DockerConfig;
use DDT\Exceptions\Docker\DockerMissingException;
use DDT\Exceptions\Docker\DockerNotRunningException;

class Docker
{
    private $config;
	private $profile;
	private $version;
    private $command = 'docker';

    public function __construct(DockerConfig $config)
	{
        $this->setConfig($config);
        $this->setProfile(new DockerRunProfile('default'));

        if(\Shell::isCommand($this->command) === false){
            throw new DockerMissingException();
        }

        $this->version = $this->getVersion();

        if(!$this->isRunning()){
            throw new DockerNotRunningException();
        }
    }

	public function getVersion(): array
    {
        return json_decode($this->run('version --format "{{json .}}"'), true);
    }

	public function isRunning(): bool
	{
	    return is_array($this->version) && array_key_exists('Server', $this->version) && !empty($this->version['Server']);
	}

    public function setConfig(DockerConfig $config): void
    {
        $this->config = $config;
    }

    public function setProfile(DockerRunProfile $profile): void
    {
        $this->profile = $profile;
    }

    public function run($command): string
    {
        $command = implode(' ', array_filter([$this->command, $this->profile->getDockerOptions(), $command]));

        return implode("\n", \Shell::exec($command));
    }
}