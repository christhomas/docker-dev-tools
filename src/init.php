<?php
if (version_compare(phpversion(), '7.2', '<')) {
    die("Sorry but the tools require at least PHP 7.2, you have ".phpversion()." installed\n");
}

spl_autoload_register(function ($class_name) {
	$file = __DIR__ . "/" . $class_name . '.php';
	if(file_exists($file)) require_once($file);

	$file = __DIR__ . '/' . strtolower(PHP_OS) . '/' . $class_name . '.php';
	if(file_exists($file)) require_once($file);

	$file = __DIR__ . '/exceptions/' . $class_name . '.php';
	if(file_exists($file)) require_once($file);
});

if(!isset($showErrors)) $showErrors = false;

$cli = new CLI($argv, $showErrors);

Shell::setDebug($cli->hasArg('debug'));

return $cli;
