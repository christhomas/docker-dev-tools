<?php
spl_autoload_register(function ($class_name) {
	$file = __DIR__ . "/" . $class_name . '.php';
	if(file_exists($file)) require_once($file);

	$file = __DIR__ . '/' . strtolower(PHP_OS) . '/' . $class_name . '.php';
	if(file_exists($file)) require_once($file);

	$file = __DIR__ . '/exceptions/' . $class_name . '.php';
	if(file_exists($file)) require_once($file);
});

if(!isset($showErrors)) $showErrors = false;

return new CLI($argv, $showErrors);
