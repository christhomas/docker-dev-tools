<?php declare(strict_types=1);

namespace DDT\Distro;

class Ubuntu_16_04 extends Linux implements DistroInterface
{
    public function enableDNS(): bool
    {
		$ipAddress = "0.0.0.0";

		\Text::print("Updating DNS Resolver to use nameserver with ip address {yel}'$ipAddress'{end}\n");
		\Text::print("{blu}Note: If you are asked for your password, it means your sudo password{end}\n");

		# Add the nameserver to the '/etc/resolvconf/resolv.conf.d/head' file if it does not exist, then call resolvconf to update everything
		$file = "/etc/resolvconf/resolv.conf.d/head";
		if(file_exists($file)){
			\Shell::exec("[ -z \"\$(cat $file | grep \"nameserver $ipAddress\")\" ] && echo \"nameserver $ipAddress\" | sudo tee -a $file");
		}
		\Shell::sudoExec("resolvconf -u");

		\Text::print("Restarting system services after reconfiguration\n");

		$file = "/etc/NetworkManager/NetworkManager.conf";
		if(file_exists($file)){
			\Shell::sudoExec("sed -i 's/^dns=dnsmasq/#dns=dnsmasq/i' $file");
		}

		$output = \Shell::sudoExec("service --status-all | grep network-manager", false, false);
		if(count($output) > 0){
			\Shell::sudoPassthru("service network-manager restart");
		}

		\Shell::sudoExec("kill -9 $(ps aux | grep [N]etworkManager/dnsmasq | awk '{ print $2 }')");
		\Shell::sudoExec("kill -9 $(ps aux | grep [d]nsmasq | awk '{ print $2 }')");

		return true;
    }

    public function disableDNS(): bool
    {
    	$ipAddress = "127.0.0.1";

    	$file = "/etc/NetworkManager/NetworkManager.conf";
    	if(file_exists($file)){
			\Shell::sudoExec("sed -i 's/^#dns=dnsmasq/dns=dnsmasq/i' $file");
		}

    	$file = "/etc/resolvconf/resolv.conf.d/head";
    	if(file_exists($file)){
			// Remove the nameserver from the resolv.conf
			\Shell::sudoExec("sed -i \"/^nameserver $ipAddress/d\" $file");
		}

		\Shell::sudoExec("resolvconf -u");

		\Shell::sudoExec("service network-manager restart", false, false);

		return true;
    }

    public function flushDNS(): void
    {
		// for linux we don't have anything to do
    }
}
