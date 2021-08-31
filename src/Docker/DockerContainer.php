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

    public function logsFollow()
    {
        $this->docker->logsFollow($this->id);
    }

    public function logs()
    {        
        $this->docker->logs($this->id);
    }

    public function stop(): bool
    {
        return false;
    }

    public function getId(): string
    {
        try{
            $id = $this->docker->inspect('container', $this->name, '-f \'{{ .Id }}\'');

            return $id[0];
        }catch(\Exception $e){
            throw new DockerContainerNotFoundException($this->name);
        }
    }

    public function exec(string $command)
    {
        return $this->docker->exec($this->id, $command, true);
    }
}