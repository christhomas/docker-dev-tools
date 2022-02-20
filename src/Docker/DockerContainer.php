<?php declare(strict_types=1);

namespace DDT\Docker;

use DDT\Exceptions\Docker\DockerContainerNotFoundException;

class DockerContainer
{
    private $docker;

    private $name;

    private $id = null;

    public function __construct(
        Docker $docker, 
        string $name, 
        ?string $command = '',
        ?string $image = null, 
        ?array $volumes = [], 
        ?array $options = [], 
        ?array $env = [], 
        ?array $ports = [],
        ?bool $background = false
    ){
        $this->docker = $docker;

        $this->name = $name;

        try{
            $this->id = $this->getId();
        }catch(DockerContainerNotFoundException $e){
            if(empty($image)){
                throw $e;
            }

            $this->run($image, $name, $command, $volumes, $options, $env, $ports, $background);

            $this->id = $this->getId();
        }
    }

    static public function get(string $name): DockerContainer
    {
        return container(DockerContainer::class, ['name' => $name]);
    }

    static public function foreground(string $name, ?string $command = '', ?string $image = null, ?array $volumes = [], ?array $options = [], ?array $env = []): void
    {
        try{
            container(DockerContainer::class, [
                'name' => $name,
                'command' => $command,
                'image' => $image,
                'volumes' => $volumes,
                'options' => $options,
                'env' => $env,
            ]);
        }catch(DockerContainerNotFoundException $e){
            // Do nothing
        }
    }

    static public function background(string $name, ?string $command = '', ?string $image = null, ?array $volumes = [], ?array $options = [], ?array $env = [], ?array $ports = []): DockerContainer
    {
        return container(DockerContainer::class, [
            'name' => $name,
            'command' => $command,
            'image' => $image,
            'volumes' => $volumes,
            'options' => $options,
            'env' => $env,
            'ports' => $ports,
            'background' => true,
        ]);
    }

    public function logs(bool $follow, ?string $since=null)
    {   
        $this->docker->logsFollow($this->id, $follow, $since);
    }

    public function getId(): string
    {
        try{
            if($this->id) return $this->id;

            $id = $this->docker->inspect('container', $this->name, '.Id');

            return current($id);
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

    public function run(string $image, string $name, string $command = '', array $volumes = [], array $options = [], array $env = [], array $ports = [], bool $background=false): int
	{
		$exec = ["run"];

		$exec[] = "--name $name";

		if($background){
			$exec[] = "-d --restart always";
		}
		
		foreach($volumes as $v){
			$exec[] = "-v $v";
		}

		foreach($env as $e){
			$exec[] = "-e $e";
		}

		foreach($ports as $p){
			$exec[] = "-p $p";
		}

		$exec = array_merge($exec, $options);

		$exec[] = $image;

		$exec[] = $command;

        return $this->docker->passthru(implode(" ", $exec));
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