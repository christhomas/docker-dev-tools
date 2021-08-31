<?php declare(strict_types=1);

use DDT\CLI;
use DDT\Text;
use DDT\Container;
use DDT\DistroDetect;
use DDT\Tool\EntrypointTool;
use DDT\Config\SystemConfig;
use DDT\Contract\IpServiceInterface;
use DDT\Contract\DnsServiceInterface;
use DDT\Exceptions\AutoloadException;

try{
	if (version_compare(phpversion(), '7.2', '<')) {
		die("Sorry but the tools require at least PHP 7.2, you have ".phpversion()." installed\n");
	}

	spl_autoload_register(function ($fqcn) {
		$class = implode('/', array_slice(explode('\\', $fqcn), 1));

		$file = __DIR__ . '/' . $class . '.php';

		if (strlen($class) && file_exists($file)) {
			return require_once($file);
		}

		throw new AutoloadException("Autoloader could not find class '$fqcn'");
	});

	function container(?string $ref = null, ?array $args = [])
	{
		if(Container::$instance === null){
			throw new \Exception("You must create the container before attempting to use it");
		}

		return is_string($ref)
			? Container::$instance->get($ref, $args)
			: Container::$instance;
	}

	$cli = new CLI($argv, new Text());

	$container = new Container($cli);

	$container->singleton(CLI::class, $cli);

	$container->singleton(SystemConfig::class, new SystemConfig($_SERVER['HOME']));

	$detect = $container->get(DistroDetect::class);

	switch(true){
		case $detect->isDarwin():
			$container->singleton(IpServiceInterface::class, \DDT\Network\Darwin\IpService::class);
			$container->singleton(DnsServiceInterface::class, \DDT\Network\Darwin\DnsService::class);
			break;

		case $detect->isUbuntu('16.04'):
		case $detect->isUbuntu('16.10'):
			$container->singleton(IpServiceInterface::class, \DDT\Network\Linux\IpService::class);
			$container->singleton(DnsServiceInterface::class, \DDT\Network\Ubuntu_16\DnsService::class);
			break;
		
		case $detect->isUbuntu('18.04'):
		case $detect->isUbuntu('18.10'):
			$container->singleton(IpServiceInterface::class, \DDT\Network\Linux\IpService::class);
			$container->singleton(DnsServiceInterface::class, \DDT\Network\Ubuntu_18\DnsService::class);
			break;
	}

	$tool = container(EntrypointTool::class);
	$tool->handle();
}catch(\Exception $e){
	$cli = container(CLI::class);
	$cli->failure('The tool has a non-specified exception: ' . $e->getMessage());
}
