<?php declare(strict_types=1);

namespace DDT\Contract;

interface DnsServiceInterface
{
    public function getHardwarePorts(): array;
    public function listIpAddress(): array;
    public function enable(string $dnsIpAddress): bool;
    public function disable(): bool;
    public function flush(): void;
}