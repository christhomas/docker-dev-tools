<?php declare(strict_types=1);

use DDT\Autowire;
use DDT\CLI;
use DDT\Text\Text;
use DDT\Container;
use DDT\DistroDetect;
use DDT\Tool\EntrypointTool;
use DDT\Config\SystemConfig;
use DDT\Contract\IpServiceInterface;
use DDT\Contract\DnsServiceInterface;
use DDT\Exceptions\Config\ConfigInvalidException;
use DDT\Exceptions\Config\ConfigMissingException;
use DDT\Exceptions\Container\ContainerNotInstantiatedException;
use DDT\Exceptions\Tool\ToolCommandNotFoundException;
use DDT\Exceptions\Tool\ToolNotFoundException;
use DDT\Exceptions\Tool\ToolNotSpecifiedException;

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

	$container = new Container($cli, [Autowire::class, 'instantiator']);

	// Simple "yes the script runs" type check
	if((bool)$cli->getArg('--are-you-ok', false, true)){
		die("yes\n");
	}
	
	// We have to set this value really early so it's useful when the autowirer starts using it
	if((bool)$cli->getArg('--dev-debug', false)){
		function debugVar($a){ is_scalar($a) ? print("$a\n") : var_dump($a); }
	}else{
		function debugVar($a){}
	}

	// Set these two important locations for either the system configuration
	// This is the default system configuration that is the basic template for any new installation
	$container->singleton('config.file.default', __DIR__ . '/../default.ddt-system.json');
	// This is the currently installed system configuration
	$container->singleton('config.file.system', $_SERVER['HOME'] . '/.ddt-system.json');

	$container->singleton(CLI::class, $cli);
	
	$container->singleton(SystemConfig::class, function() {
		static $c = null;
		
		if($c === null) {
			$installConfig = container('config.file.system');
			$defaultConfig = container('config.file.default');

			if(file_exists($installConfig)){
				$c = new SystemConfig($installConfig, false);
			}else{
				$c = new SystemConfig($defaultConfig, true);
			}
		}

		return $c;
	});

	// Set the container to have some default values which can be extracted on demand
	// This just centralises all the defaults in one place, there are other ways to do it
	// But this just seems to be a nice place since you're also setting up the rest of the di-container
	// TODO: This is already stored in the default.ddt-system.json file and should be used instead of duplicating this here
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
}catch(ConfigMissingException $e){
	$cli->failure(get_class($e) . $text->box($e->getMessage(), "wht", "red"));
}catch(ConfigInvalidException $e){
	$cli->failure($text->box($e->getMessage(), "wht", "red"));
}catch(ToolNotFoundException $e){
	$cli->failure($text->box($e->getMessage(), "wht", "red"));
}catch(ToolNotSpecifiedException $e){
	$cli->failure($text->box($e->getMessage(), "wht", "red"));
}catch(ToolCommandNotFoundException $e){
	$cli->failure($text->box($e->getMessage(), "wht", "red"));
}catch(Exception $e){
	$cli->failure($text->box(get_class($e) . ":\nThe tool has a non-specified error: " . $e->getMessage(), "wht", "red"));
}