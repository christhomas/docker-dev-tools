<?php declare(strict_types=1);

namespace DDT\Tool;

use DDT\CLI;
use DDT\Distro\DistroDetect;
use DDT\Network\Network;

class ConfigTool extends Tool
{
    /** @var \DDT\Config\SystemConfig  */
    private $config;

    /** @var Network  */
    private $network;

    public function __construct(CLI $cli, \DDT\Config\SystemConfig $config)
    {
    	parent::__construct('config', $cli);

        $this->cli = $cli;
        $this->config = $config;
		$distro = DistroDetect::get();
		$this->network = new Network($distro);
    }

    public function getTitle(): string
    {
        return 'Docker Dev Tools Configuration';
    }

    public function getShortDescription(): string
    {
        return 'A tool to manage the configuration of the overall system, setting and getting parameters from the main system config';
    }

    public function getDescription(): string
    {
        return "This tool is to manipulate your system configuration";
    }

	protected function set(): void
	{
		\Script::failure("TODO: implement set functionality");
		$this->network->createIpAddressAlias();
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
//		Text::print("IP Address: '{yel}$alias{end}'\n");
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
