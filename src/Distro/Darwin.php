<?php declare(strict_types=1);

namespace DDT\Distro;

class Darwin implements DistroInterface
{
	public function createIpAddressAlias(string $ipAddress): bool
	{
		try{
			if(!empty($ipAddress)){
				\Shell::exec("sudo ifconfig lo0 alias $ipAddress");
				return true;
			}
		}catch(\Exception $e){ }

		return false;
	}

	public function removeIpAddressAlias(string $ipAddress): bool
	{
		try{
			if(in_array($ipAddress, ['127.001', '127.0.0.1'])){
				return false;
			}

			if(!empty($ipAddress)){
				\Shell::sudoExec("ifconfig lo0 $ipAddress delete &>/dev/null");
				return true;
			}
		}catch(\Exception $e){ }

		return false;
	}

	private function enumerateNetworkInterfaces()
	{
		$interfaces = [];

		$hardwarePorts = \Shell::exec("networksetup -listnetworkserviceorder | grep 'Hardware Port'");

		foreach($hardwarePorts as $hwport){
			if(preg_match("/Hardware Port:\s+(?P<name>[^,]+),\s+Device:\s+(?P<device>[^)]+)/", $hwport, $matches)){
				try{
					$dev = implode("\n", \Shell::exec("ifconfig {$matches['device']} 2>/dev/null"));
					if(strpos($dev, "status: active") !== false){
						$interfaces[] = ['name' => $matches['name'], 'device' => $matches['device']];
					}
				}catch(\Exception $e) {
					// ignore this device
				}
			}
		}

		return $interfaces;
	}

	private function setDNS(string $message, string $ipAddress): bool
	{
		\Text::print("DNS Servers: '{yel}$message{end}' => '{yel}$ipAddress{end}'\n");

		$interfaces = $this->enumerateNetworkInterfaces();
		foreach($interfaces as $i){
			\Text::print("Configuring interface '{yel}{$i['name']}{end}'\n");
			\Shell::sudoExec("networksetup -setdnsservers '{$i['name']}' $ipAddress");
		}

		$this->flushDNS();

		return true;
	}

	public function enableDNS(): bool
	{
		// TODO: This needs to come from the configuration somehow
		$dnsIpAddress = '10.254.254.254';

		$existing = \Shell::exec("scutil --dns | grep nameserver | awk '{print $3}' | sort | uniq");
		$ipAddress = implode(' ', array_unique(array_merge([$dnsIpAddress], $existing)));

		return $this->setDNS('Docker Container', $ipAddress);
	}

	public function disableDNS(): bool
	{
		return $this->setDNS('Reset back to router', 'empty');
	}

	public function flushDNS(): void
	{
		\Text::print("Flushing DNS Cache: ");

		if(\Shell::isCommand('dscacheutil')){
			\Shell::sudoExec('dscacheutil -flushcache');
		}

		\Shell::sudoExec('killall -HUP mDNSResponder');

		\Text::print("{grn}FLUSHED{end}\n");
	}
}
