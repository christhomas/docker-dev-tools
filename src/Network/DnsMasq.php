<?php declare(strict_types=1);

namespace DDT\Network;

use DDT\CLI;
use DDT\Config\DnsConfig;
use DDT\Docker\Docker;
use DDT\Docker\DockerContainer;
use DDT\Exceptions\UnsupportedDistroException;

class DnsMasq
{
    /** @var CLI */
    private $cli;

    /** @var DnsConfig */
    private $config;

    /** @var Docker */
    private $docker;

    public function __construct(CLI $cli, DnsConfig $config, Docker $docker)
    {
        $this->cli = $cli;
        $this->config = $config;
        $this->docker = $docker;
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

            $list = array_map('trim', explode("\n", $container->exec("find /etc/dnsmasq.d -name \"*.conf\" -type f")));

            $domains = [];

            foreach($list as $file){
			    $file = trim($file);
				$contents = $container->exec("cat $file", true);
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

	public function addDomain(string $domain, string $ipAddress): bool
	{
        $container = container(DockerContainer::class, [
            'name' => $this->config->getContainerName(),
        ]);

        $container->exec("/bin/sh -c 'echo 'address=/$domain/$ipAddress' > /etc/dnsmasq.d/$domain.conf'");
        $container->exec("kill -s SIGHUP 1");

        sleep(2);

        return $this->config->addDomain($domain, $ipAddress);
	}

	public function removeDomain(string $domain, string $ipAddress)
	{
        $container = container(DockerContainer::class, [
            'name' => $this->config->getContainerName(),
        ]);

        $container->exec("/bin/sh -c 'f=/etc/dnsmasq.d/$domain.conf && [ -f \$f ] && rm \$f'");
        $container->exec("kill -s SIGHUP 1");

        sleep(2);

        return $this->config->removeDomain($domain, $ipAddress);
	}

	public function pull(): void
	{
		$dockerImage = $this->config->getDockerImage();
		$this->cli->print("{blu}Docker:{end} Pulling '{yel}$dockerImage{end}' before changing the dns\n");

		$this->docker->pull($dockerImage);
	}

    /**
     * @throws UnsupportedDistroception
     */
    public function start()
    {
        $this->pull();

		$this->stop();

		$this->cli->print("{blu}Starting DNSMasq Container...{end}\n");

        $container = container(DockerContainer::class, [
            'image' => $this->config->getDockerImage(),
            'name' => $this->config->getContainerName(),
            'ports' => ["53:53/udp"]
        ]);

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

    public function getContainerName(): string
    {
        return $this->config->getContainerName();
    }

    public function logs(bool $follow, ?string $since=null)
	{
		try{
            $container = container(DockerContainer::class, ['name' => $this->getContainerName()]);
			$container->logs($follow, $since);
        }catch(\Exception $e){
            throw new \Exception('Could not find docker container view the logs from: ' . $e->getMessage());
        }
	}
}