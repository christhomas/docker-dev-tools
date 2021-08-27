<?php declare(strict_types=1);

namespace DDT\Config;

class IpConfig
{
    public function __construct(SystemConfig $config)
    {
        $this->config = $config;
    }

	public function get(): string
	{
		return $this->config->getKey('.ip_address');
	}

	public function set(string $ipAddress)
	{
		$this->config->setKey('.ip_address', $ipAddress);
		$this->config->write();
	}
}