<?php declare(strict_types=1);

namespace DDT\Network\Ubuntu_18;

use DDT\CLI;
use DDT\Contract\DnsServiceInterface;

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
        $ipAddress = '0.0.0.0';

        $this->cli->print("{blu}DNS:{end} Writing new DNS Configuration\n");
        $this->cli->exec('sudo sed -i "s/^[#]\?DNS=.*\?/DNS='.$ipAddress.'/i" /etc/systemd/resolved.conf');
        $this->cli->exec('sudo sed -i "s/^[#]\?DNSStubListener=.*\?/DNSStubListener=no/i" /etc/systemd/resolved.conf');
        $this->cli->exec('sudo ln -sf /run/systemd/resolve/resolv.conf /etc/resolv.conf');

        $this->cli->print("{blu}DNS:{end} Restarting 'systemd-resolved' to set DNS to use local DNS server\n");
        $this->cli->exec('sudo systemctl restart systemd-resolved');

        return true;
    }

    public function disableDNS(): bool
    {
        $ipAddress = '127.0.0.1';

        $this->cli->print("{blu}DNS:{end} Resetting DNS Configuration back to sensible defaults\n");
        $this->cli->exec('sudo sed -i "s/^DNS=.*\?/#DNS=/i" /etc/systemd/resolved.conf');
        $this->cli->exec('sudo sed -i "s/^DNSStubListener=.*\?/#DNSStubListener=yes/i" /etc/systemd/resolved.conf');

        $this->cli->print("{blu}DNS:{end} Restarting 'systemd-resolved' to set DNS to use default resolver\n");
        $this->cli->exec("sudo systemctl restart systemd-resolved");

        return true;
    }

    public function flushDNS(): void
    {
        // for linux we don't have anything to do
    }
}