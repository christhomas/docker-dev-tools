<?php declare(strict_types=1);

require_once(__DIR__ . '/container.php');
require_once(__DIR__ . '/autoload.php');

$container = new Container();
$container->singleton(\DDT\CLI::class, function() use ($argv){
	$cli = new \DDT\CLI($argv);
	$cli->enableErrors($cli->hasArg('--debug'));

	return $cli;
});
$container->singleton(\DDT\Config\SystemConfig::class, function(){
	return new \DDT\Config\SystemConfig($_SERVER['HOME']);
});
$container->singleton('tool-list', function(){
	return array_map(function($t){ 
		return ['name' => str_replace(['tool', '.php'], '', strtolower(basename($t))), 'path' => $t];
	}, glob(__DIR__ . "/Tool/?*Tool.php"));
});

$cli = container(\DDT\CLI::class);

\Shell::setDebug($cli->hasArg('debug'));
\Text::setQuiet($cli->hasArg('quiet'));

return $cli;
