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
				Shell::exec("sudo ifconfig lo0 alias $ipAddress");
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
				Shell::exec("sudo ifconfig lo0 $ipAddress delete &>/dev/null");
				return true;
			}
		}catch(Exception $e){ }

		return false;
	}

	public function enumerateInterfaces()
	{
		$interfaces = [];

		$hardwarePorts = Shell::exec("networksetup -listnetworkserviceorder | grep 'Hardware Port'");

		foreach($hardwarePorts as $hwport){
			if(preg_match("/Hardware Port:\s+(?P<name>[^,]+),\s+Device:\s+(?P<device>[^)]+)/", $hwport, $matches)){
				try{
					$dev = implode("\n",Shell::exec("ifconfig {$matches['device']} 2>/dev/null"));
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

	private function changeDNS(string $mode): bool
	{
		$name = $mode === 'empty' ? 'Reset back to router' : $mode;
		$name = $mode === 'docker' ? 'Docker Container' : $name;

		if($mode === 'docker'){
			$ipAddress = '0.0.0.0';
		}else{
			$ipAddress = $mode;
		}

		Text::print("DNS Servers: '{yel}$name{end}'\n");

		$interfaces = $this->enumerateInterfaces();
		foreach($interfaces as $i){
			Text::print("Configuring interface '{yel}{$i['name']}{end}'\n");
			Shell::exec("sudo networksetup -setdnsservers '{$i['name']}' $ipAddress");
		}

		$this->flushDNS();

		return true;
	}

	public function enableDNS(): bool
	{
		return $this->changeDNS('docker');
	}

	public function disableDNS(): bool
	{
		return $this->changeDNS('empty');
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
