<?php
class Ubuntu2004
{
    public function __construct()
    {

    }

    public function enableDNS(string $ipAddress): bool
    {
        if(empty($ipAddress)){
            Text::print("{red}Configuration error: ip address must not be empty{end}");
            return false;
        }

        $resolvedConf = "/etc/systemd/resolved.conf";

        if(!file_exists("$resolvedConf")){
            Text::print("{red}Cannot find '$resolvedConf' in file system, cannot continue{end}\n");
            return false;
        }

        Text::print("{blu}DNS:{end} Writing new DNS Configuration\n");
        Shell::exec('sudo sed -i "s/^[#]\?DNS=.*\?/DNS=$1/i" /etc/systemd/resolved.conf');
        Shell::exec('sudo sed -i "s/^[#]\?DNSStubListener=.*\?/DNSStubListener=no/i" /etc/systemd/resolved.conf');

        return true;
        /*
if [[ -z $1 ]]; then plista_app_error "Must pass the dns ip address as the first parameter"; fi

text "${blu}DNS:${end} Writing new DNS Configuration"
sudo sed -i "s/^[#]\?DNS=.*\?/DNS=$1/i" /etc/systemd/resolved.conf
sudo sed -i "s/^[#]\?DNSStubListener=.*\?/DNSStubListener=no/i" /etc/systemd/resolved.conf

sudo ln -sf /run/systemd/resolve/resolv.conf /etc/resolv.conf

text "${blu}DNS:${end} Restarting 'systemd-resolved' to set DNS to use local DNS server"
sudo systemctl restart systemd-resolved
         */

        $this->flushDNS();
    }

    public function disableDNS(): void
    {
        $this->enableDNS('empty');
    }

    public function flushDNS(): void
    {
        Text::print("Flushing DNS Cache: ");

        if(Shell::isCommand('dscacheutil')){
            Shell::exec('sudo dscacheutil -flushcache');
        }

        Shell::exec('sudo killall -HUP mDNSResponder');

        Text::print("{grn}FLUSHED{end}\n");
    }
}