<?php declare(strict_types=1);

namespace DDT\Network\Linux;

use DDT\CLI;
use DDT\Contract\DnsServiceInterface;

// apt-get install network-manager

class DnsService implements DnsServiceInterface
{
    /** @var CLI */
    private $cli;

    public function __construct(CLI $cli)
    {
        $this->cli = $cli;
    }

	public function getIpAddressList(): array
	{
		throw new \Exception("TODO: write method " . __METHOD__);
	}

    public function enable(string $dnsIpAddress): bool
    {
        throw new \Exception("Implement method: " . __METHOD__);
    }

    public function disable(): bool
    {
        throw new \Exception("Implement method: " . __METHOD__);
    }

    public function flush(): void
    {
        throw new \Exception("Implement method: " . __METHOD__);
    }

    public function enableDNS(): bool
    {
		throw new \Exception("Implement method: " . __METHOD__);
    }

    public function disableDNS(): bool
    {
    	throw new \Exception("Implement method: " . __METHOD__);
    }

    public function flushDNS(): void
    {
		// for linux we don't have anything to do
    }
}
