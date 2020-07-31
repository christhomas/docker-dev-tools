<?php
class Ubuntu_18_04
{
    public function __construct()
    {

    }

    public function enableDNS(): bool
    {
        $ipAddress = '0.0.0.0';

        Text::print("{blu}DNS:{end} Writing new DNS Configuration\n");
        Shell::exec('sudo sed -i "s/^[#]\?DNS=.*\?/DNS='.$ipAddress.'/i" /etc/systemd/resolved.conf');
        Shell::exec('sudo sed -i "s/^[#]\?DNSStubListener=.*\?/DNSStubListener=no/i" /etc/systemd/resolved.conf');
        Shell::exec('sudo ln -sf /run/systemd/resolve/resolv.conf /etc/resolv.conf');

        Text::print("{blu}DNS:{end} Restarting 'systemd-resolved' to set DNS to use local DNS server\n");
        Shell::exec('sudo systemctl restart systemd-resolved');

        return true;
    }

    public function disableDNS(): bool
    {
        $ipAddress = '127.0.0.1';

        Text::print("{blu}DNS:{end} Resetting DNS Configuration back to sensible defaults\n");
        Shell::exec('sudo sed -i "s/^DNS=.*\?/#DNS=/i" /etc/systemd/resolved.conf');
        Shell::exec('sudo sed -i "s/^DNSStubListener=.*\?/#DNSStubListener=yes/i" /etc/systemd/resolved.conf');

        Text::print("{blu}DNS:{end} Restarting 'systemd-resolved' to set DNS to use default resolver\n");
        Shell::exec("sudo systemctl restart systemd-resolved");

        return true;
    }

    public function flushDNS(): void
    {
        // for linux we don't have anything to do
    }
}
