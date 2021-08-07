<?php declare(strict_types=1);

namespace DDT\Tool;

use DDT\CLI;
use DDT\Distro\DistroDetect;
use DDT\Config\SystemConfig;
use DDT\Network\Network;
class IpTool extends Tool
{
    /** @var \DDT\Config\SystemConfig  */
    private $config;

    /** @var Network  */
    private $network;

    public function __construct(CLI $cli, SystemConfig $config)
    {
    	parent::__construct('ip', $cli);

        $this->cli = $cli;
        $this->config = $config;
		$this->network = new Network(DistroDetect::get());
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
			"set=xxx: Add an IP Address to your configuration stack, this value will be remembered and used in the future",
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

	protected function set(): void
	{
		\Script::failure("TODO: implement set functionality");
// 		$this->network->createIpAddressAlias();
//		if($newIpAddress = $cli->getArgWithVal('set')){
//			Text::print("Writing IP Address '{yel}$newIpAddress{end}': ");
//
//			if($ipAddress->set($newIpAddress)){
//				Text::print("{grn}SUCCESS{end}\n");
//			}else{
//				Text::print("{red}FAILURE{end}\n");
//				Script::failure();
//			}
//		}else{
//			Script::failure("{red}You must pass an ip address to the --set=xxx parameter{end}");
//		}
	}

	protected function get(): void
	{
		\Script::failure("TODO: implement get functionality");
		/*$ipAddress = new IPAddress($config);
		$alias = $ipAddress->get();

		if($cli->hasArg('get')){
			Text::print("IP Address: '{yel}$alias{end}'\n");
		}*/
	}

	protected function add(): void
	{
		\Script::failure("TODO: implement add functionality");
//		Shell::sudo();
//		Text::print("Installing IP Address '{yel}$alias{end}': ");
//		$status = $ipAddress->install();
//
//		if($status === true){
//			Text::print("{grn}SUCCESS{end}\n");
//			Format::ping($ipAddress->ping());
//		}else{
//			Text::print("{red}FAILURE{end}\n");
//			Script::failure();
//		}
	}

	protected function remove(): void
	{
		\Script::failure("TODO: implement remove functionality");
//		Text::print("Uninstalling IP Address '{yel}$alias{end}': ");
//		$status = $ipAddress->uninstall();
//		$cli->setArg('ping', false);
//
//		if($status === true){
//			Text::print("{grn}SUCCESS{end}\n");
//		}else{
//			Text::print("{red}FAILURE{end}\n");
//			Script::failure();
//		}
	}

	protected function reset(): void
	{
		\Script::failure("TODO: implement reset functionality");
//		Text::print("{blu}Resetting IP Address:{end}\n");
//
//		Text::print("\t{blu}Uninstalling:{end} ");
//		$result = $ipAddress->uninstall() ? "{grn}success{end}" : "{red}failure{end}";
//		Text::print("$result\n");
//
//		Text::print("\t{blu}Installing:{end} ");
//		$result = $ipAddress->install() ? "{grn}success{end}" : "{red}failure{end}";
//		Text::print("$result\n");
	}

	protected function ping(): void
	{
		\Script::failure("TODO: implement ping functionality");
//		Format::ping($ipAddress->ping());
	}
}
