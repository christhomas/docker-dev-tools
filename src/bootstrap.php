<?php declare(strict_types=1);

use DDT\Tool\EntrypointTool;

try{
	if (version_compare(phpversion(), '7.2', '<')) {
		die("Sorry but the tools require at least PHP 7.2, you have ".phpversion()." installed\n");
	}

	$cli = require_once(__DIR__ . '/init.php');

	$tool = container(EntrypointTool::class);
	$tool->handle();
}catch(\Exception $e){
	$cli->failure('The tool has a non-specified exception: ' . $e->getMessage());
}
