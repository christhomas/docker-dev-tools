<?php declare(strict_types=1);

use DDT\CLI;
use DDT\Text;
use DDT\Container;
use DDT\DistroDetect;
use DDT\Config\SystemConfig;
use DDT\Contract\IpServiceInterface;
use DDT\Contract\DnsServiceInterface;

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
