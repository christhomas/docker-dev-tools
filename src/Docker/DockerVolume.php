<?php declare(strict_types=1);

namespace DDT\Docker;

use DDT\Exceptions\Docker\DockerVolumeNotFoundException;

class DockerVolume
{
    /** @var Docker */
    private $docker;

    /** @var string the name of this docker volume */
    private $name;

    /** @var string the docker volume id */
    private $id;

    public function __construct(Docker $docker, string $name, ?bool $autoCreate=true)
    {
        $this->docker = $docker;
        $this->name = $name;

        try{
            $this->id = $this->getId();
        }catch(DockerVolumeNotFoundException $e){
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
            $id = $this->docker->inspect('volume', $this->name, '-f \'{{ .Name }}\'');

            return $id[0];
        }catch(\Exception $e){
            throw new DockerVolumeNotFoundException($this->name);
        }
    }

    public function delete(): bool
    {
        // TODO: attempt to delete docker network
        // TODO: if fails to delete, throw exception
        // TODO: if succeeds to delete, return true

        return true;
    }
}