<?php declare(strict_types=1);

namespace DDT\Tool;

use DDT\CLI;
use DDT\Config\DockerConfig;
use DDT\Docker\Docker;

class DockerTool
{
    /** @var CLI $cli */
    private $cli;

    /** @var DockerConfig $config */
    private $config;
    
    public function __construct(CLI $cli, ?\SystemConfig $systemConfig)
    {
        $this->cli = $cli;
        
        $systemConfig = $systemConfig ?? new \SystemConfig();
        $dockerConfig = new DockerConfig($systemConfig);

        $this->setDocker(new Docker($dockerConfig));
        $this->setDockerConfig($dockerConfig);
    }

    public function setDocker(Docker $docker)
    {
        $this->docker = $docker;
    }

    public function setDockerConfig(DockerConfig $config)
    {
        $this->config = $config;
        $this->docker->setConfig($config);
    }

    public function handle(): void
    {
        foreach($this->cli->getArgList() as $arg){
            switch($arg['name']){
                case 'state':
                    var_dump($this->config->listProfile());
                    \Script::failure("showing state");
                    break;
            }
        }
    }
}