<?php declare(strict_types=1);

if (version_compare(phpversion(), '7.2', '<')) {
	die("Sorry but the tools require at least PHP 7.2, you have ".phpversion()." installed\n");
}

use DDT\Exceptions\Tool\ToolNotFoundException;
use DDT\Exceptions\Tool\ToolNotSpecifiedException;
use DDT\Exceptions\Tool\CommandNotFoundException;

try{
	spl_autoload_register(function ($fqcn) {
		// namespace autoloader
		$class = implode('/', array_slice(explode('\\', $fqcn), 1));

		$file = __DIR__ . '/' . $class . '.php';

		if (strlen($class) && file_exists($file)) {
			return require_once($file);
		}

		// old autoloader (deprecated)
		$search = [
			__DIR__ . '/',
			__DIR__ . '/Exceptions/',
			__DIR__ . '/Config/',
		];

		foreach($search as $base){
			$file = $base . $fqcn . '.php';

			if(file_exists($file)){
				return require_once($file);
			}
		}
	});

	if(!isset($showErrors)) $showErrors = false;

	$cli = new DDT\CLI($argv, $showErrors);

	\Shell::setDebug($cli->hasArg('debug'));
	\Text::setQuiet($cli->hasArg('quiet'));

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
