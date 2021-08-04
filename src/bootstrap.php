<?php declare(strict_types=1);

if (version_compare(phpversion(), '7.2', '<')) {
	die("Sorry but the tools require at least PHP 7.2, you have ".phpversion()." installed\n");
}

use DDT\Exceptions\Tool\ToolNotFoundException;
use DDT\Exceptions\Tool\ToolNotSpecifiedException;
use DDT\Exceptions\Tool\CommandNotFoundException;
use DDT\Tool\Tool;

try{
	$cli = require_once(__DIR__ . '/init.php');

	$systemConfig = \DDT\Config\SystemConfig::instance();

	$tool = $cli->shiftArg();

	if(empty($tool)) {
		\Text::print("{blu}DDT - Docker Dev Tools{end}\n\n");
		\Text::print("Installed Tools:\n");
		
		foreach(Tool::list() as $tool){
			$instance = Tool::instance($tool['name'], $cli, $systemConfig);
			\Text::print("  - {$instance->getName()} - {$instance->getShortDescription()}\n");
		}

		Script::die("\n\n");
	}else{
		$instance = Tool::instance($tool['name'], $cli, $systemConfig);
		$instance->handle();
	
		return $instance;
	}
}catch(\ConfigMissingException $e){
	Script::die(\Text::box($e->getMessage(), "white", "red"));
}catch(ToolNotFoundException $e){
	Script::failure($e->getMessage());
}catch(ToolNotSpecifiedException $e){
	Script::failure($e->getMessage());
}catch(CommandNotFoundException $e){
	Script::failure($e->getMessage());
}catch(\Exception $e){
	Script::failure('The tool has a non-specified exception: ' . $e->getMessage());
}
