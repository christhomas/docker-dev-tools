<?php
class DNSMasq {
	private $config;
	private $docker;
	private $network;

	private $keys = [
		'docker_image'		=> 'dns.docker_image',
		'container_name'	=> 'dns.container_name',
	];

	private $defaults = [
		'docker_image'		=> 'christhomas/supervisord-dnsmasq',
		'container_name'	=> 'ddt-dnsmasq',
	];

	public function __construct(Config $config, Docker $docker)
	{
		$this->config = $config;
		$this->docker = $docker;
		$this->network = new Network($config);

		if($this->config->getKey($this->keys['docker_image']) === null){
			$this->setDockerImage($this->defaults['docker_image']);
		}

		if($this->config->getKey($this->keys['container_name']) === null){
			$this->setContainerName($this->defaults['container_name']);
		}
	}

	public function setDockerImage(string $image): void
	{
		$this->config->setKey($this->keys['docker_image'], $image);
		$this->config->write();
	}

	public function getDockerImage(): string
	{
		return $this->config->getKey($this->keys['docker_image']);
	}

	public function setContainerName(string $containerName): void
	{
		$this->config->setKey($this->keys['container_name'], $containerName);
		$this->config->write();
	}

	public function getContainerName(): string
	{
		return $this->config->getKey($this->keys['container_name']);
	}

	public function isRunning()
	{
		return $this->docker->findRunning($this->getDockerImage()) !== null;
	}

	public function listDomains(bool $fromContainer=false)
	{
		if($fromContainer === true){
			$containerId = $this->docker->findRunning($this->getDockerImage());
			$list = $this->docker->exec($containerId, "find /etc/dnsmasq.d -name \"*.conf\" -type f");

			$domains = [];

			foreach($list as $file){
				$contents = $this->docker->exec(containerId, "cat $file", true);
				if(preg_match("/^[^\/]+\/(?P<domain>[^\/]+)\/(?P<ip_address>[^\/]+)/", $contents, $matches)){
					$domains[] = ['domain' => $matches['domain'], 'ip_address' => $matches['ip_address']];
				}
			}

			return $domains;
		}

		return $this->config->getKey('dns.domains', []);
	}

	public function addDomain(string $ipAddress, string $domain)
	{
		$containerId = $this->docker->findRunning($this->getDockerImage());

		Text::print("{blu}Installing domain:{end} '{yel}$domain{end}' with ip address '{yel}$ipAddress{end}' into dnsmasq configuration running in container '{yel}$containerId{end}'\n");

		$this->docker->exec($containerId, "/bin/sh -c 'echo 'address=/$domain/$ipAddress' > /etc/dnsmasq.d/$domain.conf'");
		$this->docker->exec($containerId, "kill -s SIGHUP 1");

		sleep(2);

		$domainList = $this->config->getKey('dns.domains', []);
		foreach($domainList as $key => $value) {
			if($value['domain'] === $domain) unset($domainList[$key]);
		}
		$domainList[] = ['domain' => $domain, 'ip_address' => $ipAddress];
		$this->config->setKey('dns.domains', array_values($domainList));
		$this->config->write();
	}

	public function removeDomain(string $domain)
	{
		$containerId = $this->docker->findRunning($this->getDockerImage());

		Text::print("{blu}Remove domain:{end} '{yel}$domain{end}' from dnsmasq configuration running in container '{yel}$containerId{end}'\n");

		$this->docker->exec($containerId, "/bin/sh -c 'f=/etc/dnsmasq.d/$domain.conf && [ -f \$f ] && rm \$f'");
		$this->docker->exec($containerId, "kill -s SIGHUP 1");

		sleep(2);

		$domainList = $this->config->getKey('dns.domains', []);
		foreach($domainList as $key => $value) {
			if($value['domain'] === $domain) unset($domainList[$key]);
		}
		$domainList = array_values($domainList);
		$this->config->setKey('dns.domains', $domainList);
		$this->config->write();
	}

	public function logs(): bool
	{
		$container = $this->docker->findRunning($this->getDockerImage());

		if($container){
			$this->docker->logs($container);
			return true;
		}else{
			return false;
		}
	}

	public function logsFollow(): bool
	{
		$container = $this->docker->findRunning($this->getDockerImage());

		if($container){
			$this->docker->logsFollow($container);
			return true;
		}else{
			return false;
		}
	}

	public function enable(): void
	{
		$this->network->enableDNS();
	}

	public function disable(): void
	{
		$this->network->disableDNS();
	}

	public function pull(): void
	{
		$dockerImage = $this->getDockerImage();
		Text::print("{blu}Docker:{end} Pulling '{yel}$dockerImage{end}' before changing the dns\n");

		$this->docker->pull($dockerImage);
	}

	public function start(): void
	{
		if(!$this->docker->isRunning()){
			Text::print("{red}Docker is not running{end}\n");
			return;
		}

		$this->pull();

		$this->stop();

		Text::print("{blu}Starting DNSMasq Container...{end}\n");

		$this->enable();

		$dockerImage = $this->getDockerImage();
		$containerName = $this->getContainerName();
		$this->docker->run($dockerImage, $containerName, ["53:53/udp"], [], true);

		sleep(2);
	}

	public function stop(): void
	{
		if(!$this->docker->isRunning()){
			Text::print("{red}Docker is not running{end}\n");
			return;
		}

		$this->disable();

		$containerId = $this->docker->findRunning($this->getDockerImage());

		if(!empty($containerId)){
			$this->docker->deleteContainer($containerId);
		}

		$containerName = $this->getContainerName();
		$containerId = $this->docker->getContainerId($containerName);

		try{
			if(!empty($containerId)) {
				$this->docker->deleteContainer($containerId);
			}
		}catch(Exception $e){
			Text::print("Exception: ".$e->getMessage());
		}
	}
}
