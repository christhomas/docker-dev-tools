<?php
class IPAddress
{
	private $key = "ip_address";
	private $default = "10.254.254.254";
	private $config = null;
	private $network = null;

	public function __construct(Config $config)
	{
		$this->config = $config;
		$this->network = new Network();
	}

	public function install(): bool
	{
		return $this->network->installIPAddress($this->get());
	}

	public function uninstall(): bool
	{
		return $this->network->uninstallIPAddress($this->get());
	}

	public function get(?string $default=null): string
	{
		$default = $default ?: $this->default;

		return $this->config->getKey($this->key, $default);
	}

	public function set(string $ipAddress): bool
	{
		$this->config->setKey($this->key, $ipAddress);
		$this->config->write();

		return $this->config->getKey($this->key) === $ipAddress;
	}

	public function ping(string $ipAddress=null, ?string $compare=null): array
	{
		$ipAddress = $ipAddress ?: $this->get();

		try{
			$result = Shell::exec("ping -c 1 -W 1 $ipAddress 2>&1");
		}catch(Exception $e){
			$result = explode("\n",$e->getMessage());
		}

		$data = [
			'hostname'		=> null,
			'ip_address'	=> null,
			'packet_loss'	=> 0.0,
			'can_resolve'	=> true,
			'matched'		=> true,
		];

		foreach($result as $line){
			if(preg_match("/^PING\s+([^\s]+)\s\(([^\)]+)\)/", $line, $matches)){
				$data['hostname'] = $matches[1];
				// We do this because pinging a hostname will return the ip address
				$data['ip_address'] = $matches[2];
			}

			// Check DNS resolution resolved to the expected domain name
			if($compare && $compare !== $data['ip_address']){
				$data['matched'] = $compare;
			}

			if(preg_match("/cannot resolve ([^\s]+): unknown host/i", $line, $matches)){
				$data['ip_address'] = $matches[1];
			}

			if(preg_match("/((?:[0-9]{1,3})(?:\.[0-9]+)?)[\s]?% packet loss/", $line, $matches)){
				$data['packet_loss'] = (float)$matches[1];
			}

			if(preg_match("/(cannot resolve|Time to live exceeded|0 packets received)/", $line, $matches)){
				$data['can_resolve'] = false;
			}
		}

		if($data['ip_address'] && $data['packet_loss'] === 0.0 && $data['can_resolve'] === true){
			$data['status'] = true;
		}else{
			$data['status'] = false;
		}

		return $data;
	}
}
