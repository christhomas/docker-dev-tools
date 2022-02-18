<?php declare(strict_types=1);

namespace DDT\Docker;

use DDT\Exceptions\Docker\DockerContainerNotFoundException;

class DockerContainer
{
    private $docker;

    private $name;

    private $id;

    public function __construct(Docker $docker, string $name, ?string $image = null, ?array $ports = [], ?array $volumes = [], ?array $options = [])
    {
        $this->docker = $docker;

        $this->name = $name;

        try{
            $this->id = $this->getId();
        }catch(DockerContainerNotFoundException $e){
            if(empty($image)){
                throw $e;
            }

            $this->docker->run($image, $name, $ports, $volumes, $options);

            $this->id = $this->getId();
        }
    }

    static public function instance(string $name, ?string $image = null, ?array $ports = [], ?array $volumes = [], ?array $options = []): DockerContainer
    {
        return container(DockerContainer::class, [
            'name' => $name,
            'image' => $image,
            'ports' => $ports,
            'volumes' => $volumes,
            'options' => $options,
        ]);
    }

    public function logs(bool $follow, ?string $since=null)
    {   
        $this->docker->logsFollow($this->id, $follow, $since);
    }

    public function getId(): string
    {
        try{
            $id = $this->docker->inspect('container', $this->name, '.Id');
            $id = $id[0];

            return $id;
        }catch(\Exception $e){
            throw new DockerContainerNotFoundException($this->name);
        }
    }

    public function isRunning(): bool
    {
        $status = $this->docker->inspect('container', $this->name, '.State.Status');
        $status = $status[0];

        return $status === 'running';
    }

    public function listNetworks(): array
    {
        return $this->docker->inspect('container', $this->name, '.NetworkSettings.Networks');
    }

    public function listEnvParams(): array
    {
        $list = $this->docker->inspect('container', $this->name, '.Config.Env');
        
        return array_reduce($list, function($a, $e) {
            [$name, $value] = explode("=", $e) + [null, null];
            
            // If any name/value is null, 
            if($name === null || $value === null){
                return $a;
            }

            $a[$name] = $value;
            return $a;
        }, []);
    }

    public function getIpAddress(string $network): string
    {
        $ipAddress = $this->docker->inspect('container', $this->name, ".NetworkSettings.Networks.{$network}.IPAddress");

        return current($ipAddress);
    }

    public function exec(string $command)
    {
        return $this->docker->exec("exec -it $this->id $command");
    }
    
    public function stop(): bool
    {
        return $this->docker->stop($this->getId());
    }

    public function delete(): bool
    {
        return $this->docker->delete($this->getId());
    }
}