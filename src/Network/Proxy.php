<?php declare(strict_types=1);

namespace DDT\Network;

use DDT\CLI;
use DDT\Config\ProxyConfig;
use DDT\Docker\Docker;
use DDT\Docker\DockerContainer;
use DDT\Exceptions\Docker\DockerContainerNotFoundException;

class Proxy
{
	private $cli;
	private $config;
	private $docker;

	public function __construct(CLI $cli, ProxyConfig $config, Docker $docker)
	{
		$this->cli = $cli;
		$this->config = $config;
		$this->docker = $docker;
	}

	public function setDockerImage(string $image): bool
	{
		return $this->config->setDockerImage($image);
	}

	public function getDockerImage(): string
	{
		return $this->config->getDockerImage();
	}

	public function setContainerName(string $name): bool
	{
		return $this->config->setContainerName($name);
	}

	public function getContainerName(): string
	{
		return $this->config->getContainerName();
	}

	public function getContainerId(): ?string
	{
        $data = $this->docker->inspect("container", $this->getContainerName());

        return is_array($data) && array_key_exists("Id", $data) ? $data["Id"] : null;
	}

	public function isRunning(): bool
	{
		try{
			$container = container(DockerContainer::class, ['name' => $this->getContainerName()]);
			var_dump(['running' => $container->getId()]);
			die('stop 1');
			return true;
		}catch(\Exception $e){
			var_dump(['not-running' => true]);
			die('stop 2');
			return false;
		}
	}

    /**
     * @return string
     * @throws ContainerNotRunningException
     */
	public function getConfig(): string
	{
		$containerId = $this->getContainerId();

		try{
			return $this->docker->exec($containerId, 'cat /etc/nginx/conf.d/default.conf');
		}catch(\Exception $e){
			$this->cli->printDebug($e->getMessage());
			return "";
		}
	}

	public function getNetworks(): array
	{
		return $this->config->getNetworkList();
	}

	public function getListeningNetworks(): array
	{
		$containerId = $this->getContainerId();

		try{
			$json = $this->docker->inspect('container', $containerId);
			$networkList = array_keys($json["NetworkSettings"]["Networks"]);
			$networkList = array_filter($networkList, function($v){
				return strpos($v, 'bridge') === false;
			});

			return $networkList;
		}catch(\Exception $e){
			return [];
		}
	}

	public function start(?array $networkList=null)
	{
		$image = $this->getDockerImage();
		$name = $this->getContainerName();
		$path = $this->config->getToolsPath();

		$this->docker->pruneContainer();

		try{
			// Remove the container that was previously built
			// cause otherwise it'll crash with "The container name /xxx" is already in use by container "xxxx"
			$container = container(DockerContainer::class, ['name' => $name]);
			$this->cli->print("Deleting Containers\n");
			$this->docker->deleteContainer($name);
		}catch(DockerContainerNotFoundException $e){
			// It's already not started or not found, so we have nothing to do
		}

		try{
			$container = container(DockerContainer::class, [
				'name' => $name,
				'image' => $image,
				'ports' => ['80:80', '443:443'],
				'volumes' => [
					"/var/run/docker.sock:/tmp/docker.sock:ro",
					"$path/proxy-config/global.conf:/etc/nginx/conf.d/global.conf",
					"$path/proxy-config/nginx-proxy.conf:/etc/nginx/proxy.conf",
				]
			]);

			$id = $container->getId();

			if(empty($networkList)){
				// use the networks from the configuration
				$networkList = $this->getNetworks();
			}
	
			foreach($networkList as $network){
				$this->cli->print("Connecting container '$id' to network '$network'\n");
				$this->docker->createNetwork($network);
				$this->docker->networkAttach($network, $id);
			}

			$this->cli->print("Running '$name', container id: '$id'\n");
		}catch(DockerContainerNotFoundException $e){
			$this->cli->failure("The container '$name' did not start correctly\n");
		}
	}

    /**
     * @throws ContainerNotRunningException
     */
	public function stop()
	{
		$containerId = $this->getContainerId();

		$this->docker->deleteContainer($containerId);

		// we don't delete the network since there is no real reason to want to do this
		// just leave it and reuse it when necessary
	}

	public function logs()
	{
		$containerId = $this->getContainerId();

		if(!empty($containerId)){
			$this->docker->logs($containerId);
		}
	}

	public function logsFollow()
	{
		$containerId = $this->getContainerId();

		if(!empty($containerId)){
			$this->docker->logsFollow($containerId);
		}
	}

	public function addNetwork(string $network)
	{
		$this->docker->createNetwork($network);

		$containerId = $this->getContainerId();

		if($this->docker->networkAttach($network, $containerId))
		{
			return $this->config->addNetwork($network);
		}else{
			// TODO: should we do anything different here?
			return false;
		}
	}

	public function removeNetwork(string $network)
	{
		if($this->docker->networkDetach($network, $this->getContainerId()))
		{
			return $this->config->removeNetwork($network);
		}else{
			// TODO: should we do anything different here?
			return false;
		}
	}

	public function getUpstreams(): array
	{
		$config = explode("\n",$this->getConfig());
		if(empty($config)) return [];

		$containers = [];
		foreach($config as $line){
			if(preg_match("/^upstream\s(?P<upstream>[^\s]+)\s\{$/", trim($line), $matches)) {
				$containers[] = $matches['upstream'];
			}
		}

		$upstream = [];
		foreach($containers as $c){
			$upstream[$c] = ['host' => '<empty>', 'port' => 80, 'path' => '/', 'networks' => '<empty>'];

			$json = $this->docker->inspect('container', $c);
			foreach($json['Config']['Env'] as $e){
				list($key, $value) = explode("=", $e);
				if($key === 'VIRTUAL_HOST') $upstream[$c]['host'] = $value;
				if($key === 'VIRTUAL_PORT') $upstream[$c]['port'] = $value;
				if($key === 'VIRTUAL_PATH') $upstream[$c]['path'] = $value;
			}

			$upstream[$c]['networks'] = implode(',', array_keys($json['NetworkSettings']['Networks']));
		}

		return $upstream;
	}
}
