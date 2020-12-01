<?php
if (version_compare(phpversion(), '7.2', '<')) {
    die("Sorry but the tools require at least PHP 7.2, you have ".phpversion()." installed\n");
}

spl_autoload_register(function ($name) {
	$search = [
		__DIR__ . '/',
		__DIR__ . '/' . ucwords(strtolower(PHP_OS)) . '/',
		__DIR__ . '/Exceptions/',
		__DIR__ . '/Config/',
	];

	foreach($search as $base){
		$file = $base . $name . '.php';
		if(file_exists($file)) require_once($file);	
	}
});

if(!isset($showErrors)) $showErrors = false;

$cli = new CLI($argv, $showErrors);

Shell::setDebug($cli->hasArg('debug'));
Text::setQuiet($cli->hasArg('quiet'));

return $cli;
