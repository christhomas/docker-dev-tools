<?php declare(strict_types=1);

use DDT\CLI;
use DDT\Text\Text;
use DDT\Container;
use DDT\DistroDetect;
use DDT\Tool\EntrypointTool;
use DDT\Config\SystemConfig;
use DDT\Contract\IpServiceInterface;
use DDT\Contract\DnsServiceInterface;
use DDT\Docker\DockerVolume;
use DDT\Exceptions\Config\ConfigInvalidException;
use DDT\Exceptions\Config\ConfigMissingException;
use DDT\Exceptions\Container\ContainerNotInstantiatedException;
use DDT\Network\Proxy;

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
		
		return false;
	});

	function container(?string $ref = null, ?array $args = [])
	{
		if(Container::$instance === null){
			throw new ContainerNotInstantiatedException();
		}

		return is_string($ref)
			? Container::$instance->get($ref, $args)
			: Container::$instance;
	}

	$text = new Text();
	$cli = new CLI($argv, $text);

	$container = new Container($cli);
	$container->singleton(CLI::class, $cli);
	$container->singleton(SystemConfig::class, new SystemConfig($_SERVER['HOME']));

	// Set the container to have some default values which can be extracted on demand
	// This just centralises all the defaults in one place, there are other ways to do it
	// But this just seems to be a nice place since you're also setting up the rest of the di-container
	$container->singleton('defaults.ip_address',			'10.254.254.254');
	$container->singleton('defaults.proxy.docker_image',	'christhomas/nginx-proxy:alpine');
	$container->singleton('defaults.proxy.container_name',	'ddt-proxy');
	$container->singleton('defaults.proxy.network',			['ddt-proxy']);
	$container->singleton('defaults.dns.docker_image',		'christhomas/supervisord-dnsmasq');
	$container->singleton('defaults.dns.container_name',	'ddt-dnsmasq');

	$detect = $container->get(DistroDetect::class);

	if($detect->isDarwin()){
		$container->singleton(IpServiceInterface::class, \DDT\Network\Darwin\IpService::class);
		$container->singleton(DnsServiceInterface::class, \DDT\Network\Darwin\DnsService::class);
	}else if($detect->isLinux()){
		$container->singleton(IpServiceInterface::class, \DDT\Network\Linux\IpService::class);

		if($detect->isUbuntu('16.04') || $detect->isUbuntu('16.10')){
			$container->singleton(DnsServiceInterface::class, \DDT\Network\Ubuntu_16\DnsService::class);
		}else if($detect->isUbuntu('18.04') || $detect->isUbuntu('18.10')){
			$container->singleton(DnsServiceInterface::class, \DDT\Network\Ubuntu_18\DnsService::class);
		}else{
			$container->singleton(DnsServiceInterface::class, \DDT\Network\Linux\DnsService::class);
		}
	}

	$tool = container(EntrypointTool::class);
	$tool->handle();
}catch(\Exception $e){
	$cli->failure($text->box('The tool has a non-specified exception: ' . $e->getMessage(), "white", "red"));
}catch(ConfigMissingException $e){
	$cli->failure($text->box($e->getMessage(), "white", "red"));
}catch(ConfigInvalidException $e){
	$cli->failure($text->box($e->getMessage(), "white", "red"));
}
