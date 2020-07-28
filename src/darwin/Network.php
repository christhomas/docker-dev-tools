<?php
class Network
{
	public function __construct()
	{

	}

	public function installIPAddress(string $ipAddress): bool
	{
		try{
			if(!empty($ipAddress)){
				Execute::run("sudo ifconfig lo0 alias $ipAddress");
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
				Execute::run("sudo ifconfig lo0 $ipAddress delete &>/dev/null");
				return true;
			}
		}catch(Exception $e){ }

		return false;
	}

	public function enumerateInterfaces()
	{
		$interfaces = [];

		$hardwarePorts = Execute::run("networksetup -listnetworkserviceorder | grep 'Hardware Port'");

		foreach($hardwarePorts as $hwport){
			if(preg_match("/Hardware Port:\s+(?P<name>[^,]+),\s+Device:\s+(?P<device>[^)]+)/", $hwport, $matches)){
				try{
					$dev = implode("\n",Execute::run("ifconfig {$matches['device']} 2>/dev/null"));
					if(strpos($dev, "status: active") !== false){
						$interfaces[] = ['name' => $matches['name'], 'device' => $matches['device']];
					}
				}catch(Exception $e) {
					// ignore this device
				}
			}
		}

		return $interfaces;
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
			Execute::run("sudo networksetup -setdnsservers '{$i['name']}' $ipAddress");
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

		if(Execute::isCommand('dscacheutil')){
			Execute::run('sudo dscacheutil -flushcache');
		}

		Execute::run('sudo killall -HUP mDNSResponder');

		Text::print("{grn}FLUSHED{end}\n");
	}
}
