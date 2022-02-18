<?php declare(strict_types=1);

namespace DDT\Network;

use DDT\CLI;
use DDT\Config\ProxyConfig;
use DDT\Docker\Docker;
use DDT\Docker\DockerContainer;
use DDT\Docker\DockerNetwork;
use DDT\Docker\DockerVolume;
use DDT\Exceptions\Docker\DockerContainerNotFoundException;
use DDT\Exceptions\Docker\DockerInspectException;
use DDT\Exceptions\Docker\DockerNetworkAlreadyAttachedException;
use DDT\Exceptions\Docker\DockerNetworkCreateException;
use DDT\Exceptions\Docker\DockerNetworkExistsException;

class Proxy
{
	/** @var CLI */
	private $cli;

	/** @var ProxyConfig */
	private $config;

	/** @var Docker */
	private $docker;

	public function __construct(CLI $cli, ProxyConfig $config, Docker $docker)
	{
		$this->cli = $cli;
		$this->config = $config;
		$this->docker = $docker;
	}

	public function setDockerImage(string $image): bool
	{
		return $this->config->setDockerImage($image);
	}

	public function getDockerImage(): string
	{
		return $this->config->getDockerImage();
	}

	public function setContainerName(string $name): bool
	{
		return $this->config->setContainerName($name);
	}

	public function getContainerName(): string
	{
		return $this->config->getContainerName();
	}

	public function getContainer(): DockerContainer
	{
		return DockerContainer::instance($this->getContainerName());
	}

	public function getContainerId(): ?string
	{
        $data = $this->docker->inspect("container", $this->getContainerName());

        return is_array($data) && array_key_exists("Id", $data) ? $data["Id"] : null;
	}

	public function isRunning(): bool
	{
		try{
			$this->getContainer();
			return true;
		}catch(\Exception $e){
			return false;
		}
	}

    /**
     * @return string
     */
	public function getConfig(): string
	{
		$containerId = $this->getContainerId();

		try{
			return implode("\n", $this->docker->exec("exec -it $containerId cat /etc/nginx/conf.d/default.conf"));
		}catch(\Exception $e){
			$this->cli->debug($e->getMessage());
			return "";
		}
	}

	public function getNetworks(): array
	{
		return $this->config->getNetworkList();
	}

	public function getListeningNetworks(): array
	{
		$containerId = $this->getContainerId();

		try{
			$json = $this->docker->inspect('container', $containerId);
			$networkList = array_keys($json["NetworkSettings"]["Networks"]);
			$networkList = array_filter($networkList, function($v){
				return strpos($v, 'bridge') === false;
			});

			return $networkList;
		}catch(\Exception $e){
			return [];
		}
	}

	public function start(?array $networkList=null)
	{
		$image = $this->getDockerImage();
		$name = $this->getContainerName();
		$path = $this->config->getToolsPath('tools');

		$this->docker->pruneContainer();

		try{
			// Remove the container that was previously built
			// cause otherwise it'll crash with "The container name /xxx" is already in use by container "xxxx"
			$container = DockerContainer::instance($name);
			$this->cli->print("Deleting Container with name '$name'\n");
			$container->stop();
			$container->delete();
		}catch(DockerContainerNotFoundException $e){
			// It's already not started or not found, so we have nothing to do
		}

		//	TODO: allow this tool to support HTTPS and production modes by creating the acme container
		//	TODO: right now it just ignores it and leaves it up to the developer to manually deploy

		//	In order to support HTTPS for production servers, we will create three empty volumes for the 
		//	acme container to possible use, if it's enabled
		DockerVolume::instance('ddt_proxy_certs');
		DockerVolume::instance('ddt_proxy_vhost');
		DockerVolume::instance('ddt_proxy_html');

		try{
			$container = DockerContainer::instance(
				$name, 
				$image, 
				['80:80', '443:443'], 
				[
					"ddt_proxy_certs:/etc/nginx/certs",
					"ddt_proxy_vhost:/etc/nginx/vhost.d",
					"ddt_proxy_html:/usr/share/nginx/html",
					"/var/run/docker.sock:/tmp/docker.sock:ro",
					"$path/proxy-config/global.conf:/etc/nginx/conf.d/global.conf",
					"$path/proxy-config/nginx-proxy.conf:/etc/nginx/proxy.conf",
				]
			);

			$id = $container->getId();

			if(empty($networkList)){
				// use the networks from the configuration
				$networkList = $this->getNetworks();
			}
	
			foreach($networkList as $network){
				$this->cli->print("Connecting container '$name' to network '$network'\n");
				$network = DockerNetwork::instance($network);
				$network->attach($id);
			}

			$this->cli->print("Running image '$image' as '$name' using container id '$id'\n");
		}catch(DockerContainerNotFoundException $e){
			$this->cli->failure("The container '$name' did not start correctly\n");
		}
	}

	public function stop()
	{
		try{
			$container = DockerContainer::instance($this->getContainerName());
			$container->delete();
		}catch(DockerInspectException $e){
			$this->cli->print("{red}".$e->getMessage."{end}\n");
		}
	}

	public function logs(bool $follow, ?string $since=null)
	{
		try{
            $container = DockerContainer::instance($this->getContainerName());
			$container->logs($follow, $since);
        }catch(\Exception $e){
            throw new \Exception('Could not find docker container view the logs from: ' . $e->getMessage());
        }
	}

	public function addNetwork(string $name): bool
	{
		try{
			$network = DockerNetwork::instance($name);

			$containerId = $this->getContainerId();

			$network->attach($containerId);

			$this->cli->print("{blu}Attaching:{end} '{yel}$name{end}' to proxy so it can listen for containers\n");
			
			return $this->config->addNetwork($name);
		}catch(DockerNetworkCreateException $e){
			$this->cli->print("{blu}Network:{end} '{yel}$name{end}' was not found, but creating it also failed\n");
		}catch(DockerNetworkAlreadyAttachedException $e){
			$this->cli->print("{blu}Network:{end} '{yel}$name{end}' was already attached to container id '$containerId'\n");
		}catch(\Exception $e){
			// TODO: should we do anything different here?
			$this->cli->debug("We have a general failure attaching the proxy to network '$name' with message: " . $e->getMessage());	
		}

		return false;
	}

	public function removeNetwork(string $name): bool
	{
		try{
			$network = DockerNetwork::instance($name);

			$containerId = $this->getcontainerId();

			$network->detach($containerId);

			$this->cli->print("{blu}Detaching:{end} '{yel}$name{end}' from the proxy so it will stop listening for containers\n");

			return $this->config->removeNetwork($name );
		}catch(\Exception $e){
			// TODO: should we do anything different here?
			$this->cli->debug("We have a general failure detaching the proxy from network '$name' with message: " . $e->getMessage());	
		}

		return false;
	}

	public function getUpstreams(): array
	{
		$config = explode("\n",$this->getConfig());
		if(empty($config)) return [];

		$containers = [];
		foreach($config as $line){
			if(preg_match("/^upstream\s(?P<upstream>[^\s]+)\s\{$/", trim($line), $matches)) {
				$containers[] = $matches['upstream'];
			}
		}

		$upstream = [];
		foreach($containers as $c){
			$upstream[$c] = ['host' => '<empty>', 'port' => 80, 'path' => '/', 'networks' => '<empty>'];

			$json = $this->docker->inspect('container', $c);
			foreach($json['Config']['Env'] as $e){
				list($key, $value) = explode("=", $e);
				if($key === 'VIRTUAL_HOST') $upstream[$c]['host'] = $value;
				if($key === 'VIRTUAL_PORT') $upstream[$c]['port'] = $value;
				if($key === 'VIRTUAL_PATH') $upstream[$c]['path'] = $value;
			}

			$upstream[$c]['networks'] = implode(',', array_keys($json['NetworkSettings']['Networks']));
		}

		return $upstream;
	}
}
