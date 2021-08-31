<?php declare(strict_types=1);

namespace DDT\Network;

use DDT\CLI;
use DDT\Config\DnsConfig;
use DDT\Docker\Docker;
use DDT\Docker\DockerContainer;

class DNSMasq
{
    /** @var CLI */
    private $cli;

    /** @var DnsConfig */
    private $config;

    /** @var Docker */
    private $docker;

	private $defaults = [
		'docker_image'		=> 'christhomas/supervisord-dnsmasq',
		'container_name'	=> 'ddt-dnsmasq',
	];

    public function __construct(CLI $cli, DnsConfig $config, Docker $docker)
    {
        $this->cli = $cli;
        $this->config = $config;
        $this->docker = $docker;

        if($this->config->getDockerImage() === null){
            $this->config->setDockerImage($this->defaults['docker_image']);
        }

        if($this->config->getContainerName() === null){
            $this->config->setContainerName($this->defaults['container_name']);
        }
    }

    public function isRunning(): bool
    {
        // FIXME: should actually return whether it's running or not
        return false;
    }


	public function listDomains(bool $fromContainer=false)
	{
        // create a new object for this container, to interact with it
        try{
            $container = container(DockerContainer::class, [
                'name' => $this->config->getContainerName(),
            ]);

            $list = $container->exec("find /etc/dnsmasq.d -name \"*.conf\" -type f");

            $domains = [];

            foreach($list as $file){
			    $file = trim($file);
				$contents = implode("\n", $container->exec("cat $file", true));
				if(preg_match("/^[^\/]+\/(?P<domain>[^\/]+)\/(?P<ip_address>[^\/]+)/", $contents, $matches)){
					$domains[] = ['domain' => $matches['domain'], 'ip_address' => $matches['ip_address']];
				}
			}

			return $domains;
        }catch(\Exception $e){
            // TODO: what should I do n this situation?
            $this->cli->debug("{red}[DOCKER-CONTAINER]: {end} ". $e->getMessage());
        }
	}

	public function addDomain(string $ipAddress, string $domain)
	{
        /* UPGRADE CODE
	    $containerId = $this->getContainerId();

        $this->cli->print("{blu}Installing domain:{end} '{yel}$domain{end}' with ip address '{yel}$ipAddress{end}' into dnsmasq configuration running in container '{yel}$containerId{end}'\n");

        $this->docker->exec($containerId, "/bin/sh -c 'echo 'address=/$domain/$ipAddress' > /etc/dnsmasq.d/$domain.conf'");
        $this->docker->exec($containerId, "kill -s SIGHUP 1");

        sleep(2);

        $domainList = $this->config->getKey($this->keys['domains']);

        foreach($domainList as $key => $value) {
            if($value['domain'] === $domain) unset($domainList[$key]);
        }

        $domainList[] = ['domain' => $domain, 'ip_address' => $ipAddress];
        $this->config->setKey($this->keys['domains'], array_values($domainList));

        if(!$this->config->write()){
            throw new \DDT\Exceptions\Config\ConfigWriteException("Could not write new '{$this->keys['domains']}' configuration");
        }
        */
	}

	public function removeDomain(string $domain)
	{
        /* UPGRADE CODE
	    $containerId = $this->getContainerId();

        $this->cli->print("{blu}Remove domain:{end} '{yel}$domain{end}' from dnsmasq configuration running in container '{yel}$containerId{end}'\n");

        $this->docker->exec($containerId, "/bin/sh -c 'f=/etc/dnsmasq.d/$domain.conf && [ -f \$f ] && rm \$f'");
        $this->docker->exec($containerId, "kill -s SIGHUP 1");

        sleep(2);

        $domainList = $this->config->getKey($this->keys['domains']);

        foreach($domainList as $key => $value) {
            if($value['domain'] === $domain) unset($domainList[$key]);
        }

        $domainList = array_values($domainList);
        $this->config->setKey($this->keys['domains'], $domainList);

        if(!$this->config->write()){
            throw new \DDT\Exceptions\Config\ConfigWriteException("Could not write new '{$this->keys['domains']}' configuration");
        }
        */
	}

	public function pull(): void
	{
		$dockerImage = $this->config->getDockerImage();
		$this->cli->print("{blu}Docker:{end} Pulling '{yel}$dockerImage{end}' before changing the dns\n");

		$this->docker->pull($dockerImage);
	}

    /**
     * @throws UnsupportedDistroException
     */
    public function start()
    {
        $this->pull();

		$this->stop();

		$this->cli->print("{blu}Starting DNSMasq Container...{end}\n");

		$dockerImage = $this->config->getDockerImage();
		$containerName = $this->config->getContainerName();
		$this->docker->run($dockerImage, $containerName, ["53:53/udp"]);

		sleep(2);
    }

    /**
     * @throws UnsupportedDistroException
     */
	public function stop(bool $delete=true): void
	{
        /* UPGRADE CODE
		$containerId = $this->docker->findRunning($this->config->getDockerImage());

		if(!empty($containerId)){
			$this->docker->deleteContainer($containerId);
		}

		$containerName = $this->config->getContainerName();
		$containerId = $this->docker->getContainerId($containerName);

		try{
			if(!empty($containerId)) {
				$this->docker->deleteContainer($containerId);
			}
		}catch(\Exception $e){
			$this->cli->print("Exception: ".$e->getMessage());
		}
        */
	}

    public function refresh()
    {
        $this->cli->sudo();
    }

    private function getContainerId()
    {
        return null;
    }

    public function logs(bool $follow=false)
    {
        $containerId = $this->getContainerId() ?? '';

        if(empty($containerId)){
            throw new \Exception('Could not find docker container id to view the logs from');
        }

        if($follow){
            $this->docker->logsFollow($containerId);
        }else{
            $this->docker->logs($containerId);
        }
    }
}