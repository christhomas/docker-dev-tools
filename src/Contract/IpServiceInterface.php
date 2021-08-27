<?php declare(strict_types=1);

namespace DDT\Contract;

interface IpServiceInterface
{
    public function set(string $ipAddress): bool;
	public function remove(string $ipAddress): bool;
}