<?php declare(strict_types=1);

if (version_compare(phpversion(), '7.2', '<')) {
	die("Sorry but the tools require at least PHP 7.2, you have ".phpversion()." installed\n");
}

use DDT\Exceptions\Tool\ToolNotFoundException;
use DDT\Exceptions\Tool\ToolNotSpecifiedException;
use DDT\Exceptions\Tool\CommandNotFoundException;

try{
	$cli = require_once(__DIR__ . '/init.php');

	$tool = $cli->shiftArg();

	if(empty($tool)) {
		throw new ToolNotSpecifiedException();
	}

	$class = 'DDT\\Tool\\'.ucwords($tool['name']).'Tool';

	if(class_exists($class) === false){
		throw new ToolNotFoundException($tool['name']);
	}

	$config = new \SystemConfig();
	$handler = new $class($cli, $config);
	$handler->handle();

	return $handler;
}catch(ToolNotFoundException $e){
	Script::failure($e->getMessage());
}catch(ToolNotSpecifiedException $e){
	Script::failure($e->getMessage());
}catch(CommandNotFoundException $e){
	Script::failure($e->getMessage());
}catch(\Exception $e){
	Script::failure('The tool has a non-specified exception: ' . $e->getMessage());
}
