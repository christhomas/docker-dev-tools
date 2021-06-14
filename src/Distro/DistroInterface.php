<?php declare(strict_types=1);

namespace DDT\Distro;

interface DistroInterface
{
    public function createIpAddressAlias(string $ipAddress): bool;
	public function removeIpAddressAlias(string $ipAddress): bool;
	
	public function enableDNS(): bool;
	public function disableDNS(): bool;
	public function flushDNS(): void;
}