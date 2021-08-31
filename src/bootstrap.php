<?php declare(strict_types=1);

try{
	if (version_compare(phpversion(), '7.2', '<')) {
		die("Sorry but the tools require at least PHP 7.2, you have ".phpversion()." installed\n");
	}

	require_once(__DIR__ . '/autoload.php');
	require_once(__DIR__ . '/services.php');

	$tool = container(\DDT\Tool\EntrypointTool::class);
	$tool->handle();
}catch(\Exception $e){
	$cli = container(\DDT\CLI::class);
	$cli->failure('The tool has a non-specified exception: ' . $e->getMessage());
}
