<?php
class Proxy
{
	private $config;
	private $docker;

	private $keys = [
		'docker_image'		=> 'proxy.docker_image',
		'container_name'	=> 'proxy.container_name',
		'network'			=> 'proxy.network',
	];

	private $defaults = [
		'docker_image'		=> 'christhomas/nginx-proxy:alpine',
		'container_name'	=> 'ddt-proxy',
		'network'			=> 'ddt-proxy'
	];

	public function __construct(Config $config, Docker $docker)
	{
		$this->config = $config;
		$this->docker = $docker;

		if($this->config->getKey($this->keys['docker_image']) === null){
			$this->setDockerImage($this->defaults['docker_image']);
		}

		if($this->config->getKey($this->keys['container_name']) === null){
			$this->setContainerName($this->defaults['container_name']);
		}

		if($this->config->getKey($this->keys['network']) === null){
			$this->setContainerName($this->defaults['network']);
		}
	}

	public function setDockerImage(string $image): bool
	{
		if(!empty($image)){
			$this->config->setKey($this->keys['docker_image'], $image);
			$this->config->write();

			return true;
		}else{
			return false;
		}
	}

	public function getDockerImage(): string
	{
		return $this->config->getKey($this->keys['docker_image']);
	}

	public function setContainerName(string $name): bool
	{
		if(!empty($name)) {
			$this->config->setKey($this->keys['container_name'], $name);
			$this->config->write();

			return true;
		}else{
			return false;
		}
	}

	public function getContainerName(): string
	{
		return $this->config->getKey($this->keys['container_name']);
	}

	public function getContainerId(): ?string
	{
		return $this->docker->findRunning($this->getDockerImage());
	}

	public function isRunning(): bool
	{
		return $this->getContainerId() !== null;
	}

	public function getConfig(): string
	{
		$containerId = $this->getContainerId();

		try{
			return implode("\n", Shell::exec("docker exec -it $containerId cat /etc/nginx/conf.d/default.conf"));
		}catch(Exception $e){
			return "";
		}
	}

	public function getNetworks(): array
	{
		return $this->config->getKey($this->keys['network'], []);
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
		}catch(Exception $e){
			return [];
		}
	}

	public function start(?array $networkList=null)
	{
		$dockerImage = $this->getDockerImage();
		$proxy = $this->getContainerName();
		$path = $this->config->getToolsPath();

		$this->docker->pruneContainer();

		// Remove the container that was previously built
		// cause otherwise it'll crash with "The container name /xxx" is already in use by container "xxxx"
		if($this->docker->findContainer($proxy) !== null){
			Text::print("Deleting Containers\n");
			$this->docker->deleteContainer($proxy);
		}

		$containerId = $this->docker->run(
			$dockerImage,
			$proxy,
			['80:80', '443:443'],
			[
				"/var/run/docker.sock:/tmp/docker.sock:ro",
				"$path/proxy-config/global.conf:/etc/nginx/conf.d/global.conf",
				"$path/proxy-config/nginx-proxy.conf:/etc/nginx/proxy.conf",
			],
			true
		);

		if(empty($networkList)){
			// use the networks from the configuration
			$networkList = $this->getNetworks();
		}

		foreach($networkList as $network){
			Text::print("Connecting container '$containerId' to network '$network\n");
			$this->docker->createNetwork($network);
			$this->docker->connectNetwork($network, $containerId);
		}

		if(!empty($containerId)){
			Text::print("Running '$proxy', container id: '$containerId'\n");
		}else{
			Script::failure("The container '$proxy' did not start correctly\n");
		}
	}

	public function stop()
	{
		$containerId = $this->getContainerId();

		Shell::exec("docker kill $containerId &>/dev/null");
		Shell::exec("docker rm -f $containerId &>/dev/null");

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

		if($this->docker->connectNetwork($network, $containerId))
		{
			$networkList = $this->config->getKey($this->keys['network'], []);
			$networkList[] = $network;
			$this->config->setKey($this->keys['network'], array_unique($networkList));
			$this->config->write();
		}
	}

	public function removeNetwork(string $network)
	{
		$containerId = $this->getContainerId();
		if($this->docker->disconnectNetwork($network, $containerId))
		{
			$networkList = $this->config->getKey($this->keys['network'], []);
			foreach($networkList as $index => $name){
				if($name === $network) unset($networkList[$index]);
			}
			$this->config->setKey($this->keys['network'], array_unique(array_values($networkList)));
			$this->config->write();
		}
	}

	public function getUpstreams(): array
	{
		$config = explode("\n",$this->getConfig());
		if(empty($config)) return [];

		$containers = [];
		foreach($config as $line){
			if(preg_match("/^upstream\s(?P<upstream>[^\s]+)\s\{$/", $line, $matches)){
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
