<?php declare(strict_types=1);

namespace DDT\Docker;

use DDT\CLI;
use DDT\Exceptions\Docker\DockerNetworkNotFoundException;

class DockerNetwork
{
    /** @var CLI */
    private $cli;

    /** @var Docker */
    private $docker;

    /** @var string the name of this docker network */
    private $name;

    /** @var string the docker network id */
    private $id;

    public function __construct(CLI $cli, Docker $docker, string $name)
    {
        $this->cli = $cli;
        $this->docker = $docker;
        $this->name = $name;

        try{
            $this->id = $this->getId();
        }catch(DockerNetworkNotFoundException $e){
            $this->id = $this->docker->createNetwork($this->name);
        }
    }

    public function getId(): string
    {
        try{
            $id = $this->docker->inspect('network', $this->name, '-f \'{{ .Id }}\'');

            return $id[0];
        }catch(\Exception $e){
            throw new DockerNetworkNotFoundException($this->name);
        }
    }

    public function listContainers(): array
    {
        $result = $this->docker->inspect('network', $this->name, '-f \'{{json .Containers }}\'');
        
        return array_reduce(array_keys($result), function($a, $c) use ($result) {
            $a[$c] = $result[$c]['Name'];
            return $a;
        }, []);
    }

    public function delete(): bool
    {
        // TODO: attempt to delete docker network
        // TODO: if fails to delete, throw exception
        // TODO: if succeeds to delete, return true

        return true;
    }

	public function attach($containerId): ?bool
	{
        try{
            $this->docker->networkAttach($this->name, $containerId);

            $containers = $this->listContainers();
    
            foreach($containers as $id => $name){
                if($id === $containerId) return true;
            }
        }catch(\Exception $e){
            $this->cli->debug("{red}[DOCKER]:{end} ".$e->getMessage());
        }

        return false;
	}

	public function detach(string $containerId): bool
	{
		try{
            $this->docker->networkDetach($this->name, $containerId);

            $containers = $this->listContainers();

            foreach($containers as $id => $name){
                if($id === $containerId) return false;
            }
			
            return true;
		}catch(\Exception $e){
            $this->cli->debug("{red}[DOCKER]:{end} ".$e->getMessage());
			return false;
		}
	}
}