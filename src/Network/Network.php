<?php declare(strict_types=1);

namespace DDT\Network;

use DDT\Distro\DistroInterface;

class Network
{
    private $distro;

    public function __construct(DistroInterface $distro)
    {
        $this->distro = $distro;
    }

    public function createIpAddressAlias(string $ipAddress): bool
    {
        return $this->distro->createIpAddressAlias($ipAddress);
    }

	public function removeIpAddressAlias(string $ipAddress): bool
    {
        return $this->distro->removeIpAddressAlias($ipAddress);
    }
	
	public function enableDNS(): bool
    {
        return $this->distro->enableDNS();
    }

	public function disableDNS(): bool
    {
        return $this->distro->disableDNS();
    }

	public function flushDNS(): void
    {
        $this->distro->flushDNS();
    }
}