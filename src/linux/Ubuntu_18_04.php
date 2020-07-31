<?php
class Ubuntu_18_04
{
    public function __construct()
    {

    }

    public function enableDNS(string $ipAddress): bool
    {
        $name = $ipAddress === 'empty' ? 'Reset back to router' : $ipAddress;
        $name = $ipAddress === 'docker' ? 'Docker Container' : $name;

        $ipAddress = '0.0.0.0';
        
        if(empty($ipAddress)){
            Text::print("{red}Configuration error: ip address must not be empty{end}\n");
            return false;
        }

        $resolvedConf = "/etc/systemd/resolved.conf";

        if(!file_exists("$resolvedConf")){
            Text::print("{red}Cannot find '$resolvedConf' in file system, cannot continue{end}\n");
            return false;
        }

        // I hate that these are similar names, but what else should I call it? 
        $resolvConf = "/run/systemd/resolve/resolv.conf";

        if(!file_exists("$resolvConf")){
            Text::print("{red}Cannot find '$resolvConf' in file system, cannot continue{end}\n");
            return false;
        }


        Text::print("{blu}DNS:{end} Writing new DNS Configuration\n");
        Shell::exec('sudo sed -i "s/^[#]\?DNS=.*\?/DNS='.$ipAddress.'/i" /etc/systemd/resolved.conf');
        Shell::exec('sudo sed -i "s/^[#]\?DNSStubListener=.*\?/DNSStubListener=no/i" /etc/systemd/resolved.conf');
        Shell::exec('sudo ln -sf /run/systemd/resolve/resolv.conf /etc/resolv.conf');

        Text::print("{blu}DNS:{end} Restarting 'systemd-resolved' to set DNS to use local DNS server\n");
        Shell::exec('sudo systemctl restart systemd-resolved');

        return true;
    }

    public function disableDNS(string $ipAddress): bool
    {
        if(empty($ipAddress)){
            Text::print("{red}Configuration error: ip address must not be empty{end}\n");
            return false;
        }

        $ipAddress = '0.0.0.0';

        $resolvedConf = "/etc/systemd/resolved.conf";

        if(!file_exists("$resolvedConf")){
            Text::print("{red}Cannot find '$resolvedConf' in file system, cannot continue{end}\n");
            return false;
        }

        // I hate that these are similar names, but what else should I call it? 
        $resolvConf = "/run/systemd/resolve/resolv.conf";

        if(!file_exists("$resolvConf")){
            Text::print("{red}Cannot find '$resolvConf' in file system, cannot continue{end}\n");
            return false;
        }

        Text::print("{blu}DNS:{end} Resetting DNS Configuration back to sensible defaults\n");
        Shell::exec('sudo sed -i "s/^DNS='.$ipAddress.'/#DNS=/i" /etc/systemd/resolved.conf');
        Shell::exec('sudo sed -i "s/^DNSStubListener=.*\?/#DNSStubListener=yes/i" /etc/systemd/resolved.conf');

        Text::print("{blu}DNS:{end} Restarting 'systemd-resolved' to set DNS to use default resolver\n");
        Shell::exec("sudo systemctl restart systemd-resolved");

        //Shell::exec("sudo kill -9 $(ps aux | grep [N]etworkManager/dnsmasq | awk '{ print $2 }')");
        //Shell::exec("sudo kill -9 $(ps aux | grep [d]nsmasq | awk '{ print $2 }')");

        return true;
    }

    public function flushDNS(): void
    {
        // for linux we don't have anything to do
    }
}