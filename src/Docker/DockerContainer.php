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
            debugVar("WE HAVE AN EXCEPTION");

            if(empty($image)){
                throw $e;
            }

            $this->docker->run($image, $name, $ports, $volumes, $options);

            $this->id = $this->getId();
        }
    }

    static public function instance(string $name, ?string $image = null, ?array $ports = [], ?array $volumes = [], ?array $options = []): DockerContainer
    {
        debugVar([__METHOD__ => func_get_args()]);

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