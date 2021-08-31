<?php declare(strict_types=1);

use DDT\Container;
use DDT\DistroDetect;
use DDT\Contract\IpServiceInterface;
use DDT\Contract\DnsServiceInterface;

$cli = new \DDT\CLI($argv);

$container = new Container($cli);

$container->singleton(\DDT\CLI::class, $cli);

$container->singleton(\DDT\Config\SystemConfig::class, new \DDT\Config\SystemConfig($_SERVER['HOME']));

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
