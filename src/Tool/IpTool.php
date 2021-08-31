<?php declare(strict_types=1);

namespace DDT\Tool;

use DDT\CLI;
use DDT\Config\SystemConfig;
use DDT\Contract\IpServiceInterface;

class IpTool extends Tool
{
    /** @var \DDT\Config\SystemConfig  */
    private $config;

	/** @var IpServiceInterface */
	private $ipService;

    public function __construct(CLI $cli, SystemConfig $config, IpServiceInterface $ipService)
    {
    	parent::__construct('ip', $cli);

        $this->cli = $cli;
        $this->config = $config;
		$this->ipService = $ipService;
    }

    public function getTitle(): string
    {
        return 'IP Address Tool';
    }

    public function getShortDescription(): string
    {
        return 'A tool to configure and control local ip addresses used to enable the dns server';
    }

    public function getDescription(): string
    {
		return "This tool creates an alias for {yel}localhost{end}/{yel}127.0.0.1{end} on your machine which is 
addressable from your local machine and from inside docker containers. This is useful when wanting 
to connect xdebug from your software running inside a container, to your local machine where your 
IDE Is listening for incoming connections";
    }

    public function getOptions(): string
	{
		$alias = $this->config->getKey('.ip_address') ?? 'unknown';

		return "\t" . implode("\n\t", [
			"set <ip-address>: Add an IP Address to your configuration stack, this value will be remembered and used in the future",
			"get: Get the Currently configured IP Address.",
			"add: Add '{yel}$alias{end}' as an ip alias for '{yel}127.0.0.1{end}'",
			"remove: Remove '{yel}$alias{end}' from your computer",
			"reset: Remove and Add the configuration again, just in case it broke somehow",
			"ping: Ping the configured ip address",
		]);
	}

    public function getNotes(): string
	{
		return<<<NOTES
Please don't use '{yel}localhost{end}' or '{yel}127.0.0.1{end}'

The problem is that inside a docker container, '{yel}localhost{end}' and '{yel}127.0.0.1{end}' resolves to itself. 
This means you have no ip address which is addressable from your local machine, or inside docker containers.
NOTES;
	}

	public function setCommand(): void
	{
		$ipAddress = $this->cli->shiftArg();

		if(empty($ipAddress)){
			throw new \Exception("You must provide an ip address to this command, one was not found in the config, nor the command line");
		}else{
			$ipAddress = $ipAddress['name'];
			$this->cli->print("Writing IP Address '{yel}$ipAddress{end}': ");

			$this->config->setKey('.ip_address', $ipAddress);
			if($this->config->write()){
				$this->cli->print("{grn}SUCCESS{end}\n");
			}else{
				$this->cli->print("{red}FAILURE{end}\n");
				$this->cli->failure();
			}
		}
	}

	protected function getCommand(): string
	{
		return $this->config->getKey('.ip_address');
	}

	protected function addCommand(): void
	{
		$ipAddress = $this->config->getKey('.ip_address');

		if(empty($ipAddress)){
			throw new \Exception('There is no ip address configured, you must use the \'set\' command to configure one');
		}

		$this->cli->sudo();
		$this->cli->print("Installing IP Address '{yel}$ipAddress{end}': ");
		
		if($this->ipService->set($ipAddress)){
			$this->cli->print("{grn}SUCCESS{end}\n");
		}else{
			$this->cli->print("{red}FAILURE{end}\n");
		}
	}

	protected function removeCommand(): void
	{
		$ipAddress = $this->config->getKey('.ip_address');

		if(empty($ipAddress)){
			throw new \Exception('There is no ip address configured, you must use the \'set\' command to configure one');
		}

		$this->cli->sudo();
		$this->cli->print("Uninstalling IP Address '{yel}$ipAddress{end}': ");

		if($this->ipService->remove($ipAddress)){
			$this->cli->print("{grn}SUCCESS{end}\n");
		}else{
			$this->cli->print("{red}FAILURE{end}\n");
		}
	}

	protected function resetCommand(): void
	{
		$this->cli->print("{blu}Resetting IP Address:{end}\n");
		$this->removeCommand();
		$this->addCommand();
	}

	protected function pingCommand(): void
	{
		$this->cli->failure("TODO: implement ping functionality");
		/*
		$ipAddress = $ipAddress ?: $this->get();

		try{
			$result = $this->cli->exec("ping -c 1 -W 1 $ipAddress 2>&1");
		}catch(Exception $e){
			$result = explode("\n",$e->getMessage());
		}

		$data = [
			'hostname'		=> null,
			'ip_address'	=> null,
			'packet_loss'	=> 0.0,
			'can_resolve'	=> true,
			'matched'		=> true,
		];

		foreach($result as $line){
			if(preg_match("/^PING\s+([^\s]+)\s\(([^\)]+)\)/", $line, $matches)){
				$data['hostname'] = $matches[1];
				// We do this because pinging a hostname will return the ip address
				$data['ip_address'] = $matches[2];
			}

			// Check DNS resolution resolved to the expected domain name
			if($compare && $compare !== $data['ip_address']){
				$data['matched'] = $compare;
			}

			if(preg_match("/cannot resolve ([^\s]+): unknown host/i", $line, $matches)){
				$data['ip_address'] = $matches[1];
			}

			if(preg_match("/((?:[0-9]{1,3})(?:\.[0-9]+)?)[\s]?% packet loss/", $line, $matches)){
				$data['packet_loss'] = (float)$matches[1];
			}

			if(preg_match("/(cannot resolve|Time to live exceeded|0 packets received)/", $line, $matches)){
				$data['can_resolve'] = false;
			}
		}

		if($data['ip_address'] && $data['packet_loss'] === 0.0 && $data['can_resolve'] === true){
			$data['status'] = true;
		}else{
			$data['status'] = false;
		}

		return $data;
		*/
//		Format::ping($ipAddress->ping());
	}
}
