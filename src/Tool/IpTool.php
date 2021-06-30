<?php declare(strict_types=1);

namespace DDT\Tool;

use DDT\CLI;
use DDT\Distro\DistroDetect;
use DDT\Network\Network;

class IpTool extends BaseTool
{
    /** @var \SystemConfig  */
    private $config;

    /** @var Network  */
    private $network;

    public function __construct(CLI $cli, \SystemConfig $config)
    {
    	parent::__construct('ip', $cli);

        $this->cli = $cli;
        $this->config = $config;
		$distro = DistroDetect::get();
		$this->network = new Network($distro);
    }

    protected function help(): void
	{
		\Text::print(file_get_contents($this->config->getToolsPath("/help/{$this->name}.txt")));
		\Script::die();
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
