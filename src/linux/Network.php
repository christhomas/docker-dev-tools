<?php
class Network
{
	public function __construct()
	{
	    $distro = Shell::exec("lsb_release -d")
	}

	public function installIPAddress(string $ipAddress): bool
	{
		try{
			if(!empty($ipAddress)){
				Shell::exec("sudo ip addr add $ipAddress/24 dev lo label lo:40");
				return true;
			}
		}catch(Exception $e){ }

		return false;
	}

	public function uninstallIPAddress(string $ipAddress): bool
	{
		try{
			if(in_array($ipAddress, ['127.001', '127.0.0.1'])){
				return false;
			}

			if(!empty($ipAddress)){
				Shell::exec("sudo ip addr del $ipAddress/24 dev lo");
				return true;
			}
		}catch(Exception $e){ }

		return false;
	}

	public function enableDNS(string $ipAddress)
	{
		$name = $ipAddress === 'empty' ? 'Reset back to router' : $ipAddress;
		$name = $ipAddress === 'docker' ? 'Docker Container' : $name;

		if($ipAddress === 'docker'){
			$ipAddress = '0.0.0.0';
		}

		Text::print("DNS Servers: '{yel}$name{end}'\n");

		$interfaces = $this->enumerateInterfaces();
		foreach($interfaces as $i){
			Text::print("Configuring interface '{yel}{$i['name']}{end}'\n");
			Shell::exec("sudo networksetup -setdnsservers '{$i['name']}' $ipAddress");
		}

		$this->flushDNS();
	}

	public function disableDNS()
	{
		$this->enableDNS('empty');
	}

	public function flushDNS()
	{
		Text::print("Flushing DNS Cache: ");

		if(Shell::isCommand('dscacheutil')){
			Shell::exec('sudo dscacheutil -flushcache');
		}

		Shell::exec('sudo killall -HUP mDNSResponder');

		Text::print("{grn}FLUSHED{end}\n");
	}
}
