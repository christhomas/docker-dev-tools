<?php declare(strict_types=1);

namespace DDT\Tool;

use DDT\CLI;
use DDT\Config\DockerConfig;
use DDT\Docker\Docker;

class DockerTool extends Tool
{
    /** @var DockerConfig $config */
    private $config;
    
    public function __construct(CLI $cli, ?\DDT\Config\SystemConfig $systemConfig)
    {
        parent::__construct('docker', $cli);
        
        $systemConfig = $systemConfig ?? \DDT\Config\SystemConfig::instance();
        $dockerConfig = new DockerConfig($systemConfig);

        $this->setDocker(new Docker($dockerConfig));
        $this->setDockerConfig($dockerConfig);
    }

    public function getTitle(): string
    {
        return 'The Tool Title';
    }

    public function getShortDescription(): string
    {
        return 'A tool to interact with docker enhanced by the dev tools to provide extra functionality';
    }

    public function getDescription(): string
    {
        return "There is no description";
    }

    public function getHelp(): string
    {
        return 'The Help Template';
    }

    public function help(): void{}

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