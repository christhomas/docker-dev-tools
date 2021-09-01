<?php declare(strict_types=1);

namespace DDT\Config;

use DDT\Docker\DockerRunProfile;

class DnsConfig
{
    private $keys = [
		'docker_image'		=> 'dns.docker_image',
		'container_name'	=> 'dns.container_name',
        'domains'           => 'dns.domains',
	];

    public function __construct(SystemConfig $config)
    {
        $this->config = $config;
	
		if($this->config->getKey($this->keys['docker_image']) === null){
			$this->config->setDockerImage(container('defaults.dns.docker_image'));
		}
	
		if($this->config->getKey($this->keys['container_name']) === null){
			$this->config->setContainerName(container('defaults.dns.container_name'));
		}
    }

	public function setDockerImage(string $image): string
	{
		$this->config->setKey($this->keys['docker_image'], $image);
		
		if($this->config->write()){
			return $image;
		}

		throw new \Exception('failed to write new dns docker image');
	}

	public function getDockerImage(): string
	{
		return $this->config->getKey($this->keys['docker_image']);
	}

	public function setContainerName(string $containerName): string
	{
		$this->config->setKey($this->keys['container_name'], $containerName);
		
		if($this->config->write()){
			return $containerName;
		}

		throw new \Exception('failed to write new dns container name');
	}

	public function getContainerName(): string
	{
		return $this->config->getKey($this->keys['container_name']);
	}
}