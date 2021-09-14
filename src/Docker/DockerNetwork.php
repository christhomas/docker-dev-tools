<?php declare(strict_types=1);

namespace DDT\Docker;

use DDT\Exceptions\Docker\DockerNetworkNotFoundException;

class DockerNetwork
{
    /** @var Docker */
    private $docker;

    /** @var string the name of this docker network */
    private $name;

    /** @var string the docker network id */
    private $id;

    public function __construct(Docker $docker, string $name, ?bool $autoCreate=true)
    {
        $this->docker = $docker;
        $this->name = $name;

        try{
            $this->id = $this->getId();
        }catch(DockerNetworkNotFoundException $e){
            if($autoCreate){
                $this->id = $this->docker->createNetwork($this->name);
            }else{
                throw $e;
            }
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

	public function attach($containerId): void
	{
        $this->docker->networkAttach($this->name, $containerId);

        $containers = $this->listContainers();

        foreach($containers as $id => $name){
            if($id === $containerId) return;
        }

        throw new \Exception("Failed to attach container '$containerId' to network '$this->name'");
	}

	public function detach(string $containerId): void
	{
        $this->docker->networkDetach($this->name, $containerId);

        $containers = $this->listContainers();

        foreach($containers as $id => $name){
            if($id === $containerId) {
                throw new \Exception("Failed to detach container '$containerId' from network '$this->name'");
            }
        }
	}
}