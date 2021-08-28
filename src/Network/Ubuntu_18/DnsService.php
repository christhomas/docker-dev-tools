<?php declare(strict_types=1);

namespace DDT\Network\Ubuntu_18;

use DDT\Contract\DnsServiceInterface;

class DnsService implements DnsServiceInterface
{
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
        $ipAddress = '0.0.0.0';

        \Text::print("{blu}DNS:{end} Writing new DNS Configuration\n");
        \Shell::sudoExec('sed -i "s/^[#]\?DNS=.*\?/DNS='.$ipAddress.'/i" /etc/systemd/resolved.conf');
        \Shell::sudoExec('sed -i "s/^[#]\?DNSStubListener=.*\?/DNSStubListener=no/i" /etc/systemd/resolved.conf');
        \Shell::sudoExec('ln -sf /run/systemd/resolve/resolv.conf /etc/resolv.conf');

        \Text::print("{blu}DNS:{end} Restarting 'systemd-resolved' to set DNS to use local DNS server\n");
        \Shell::sudoExec('systemctl restart systemd-resolved');

        return true;
    }

    public function disableDNS(): bool
    {
        $ipAddress = '127.0.0.1';

        \Text::print("{blu}DNS:{end} Resetting DNS Configuration back to sensible defaults\n");
        \Shell::sudoExec('sed -i "s/^DNS=.*\?/#DNS=/i" /etc/systemd/resolved.conf');
        \Shell::sudoExec('sed -i "s/^DNSStubListener=.*\?/#DNSStubListener=yes/i" /etc/systemd/resolved.conf');

        \Text::print("{blu}DNS:{end} Restarting 'systemd-resolved' to set DNS to use default resolver\n");
        \Shell::sudoExec("systemctl restart systemd-resolved");

        return true;
    }

    public function flushDNS(): void
    {
        // for linux we don't have anything to do
    }
}