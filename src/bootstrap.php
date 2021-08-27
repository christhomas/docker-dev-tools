<?php declare(strict_types=1);

if (version_compare(phpversion(), '7.2', '<')) {
	die("Sorry but the tools require at least PHP 7.2, you have ".phpversion()." installed\n");
}

use DDT\Docker\DockerNetwork;
use DDT\Exceptions\Tool\ToolNotFoundException;
use DDT\Exceptions\Tool\ToolNotSpecifiedException;
use DDT\Exceptions\Tool\CommandNotFoundException;
use DDT\Exceptions\Config\ConfigMissingException;
use DDT\Tool\EntrypointTool;

try{
	$cli = require_once(__DIR__ . '/init.php');

	$tool = container(EntrypointTool::class);
	$tool->handle();
}catch(ConfigMissingException $e){
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
