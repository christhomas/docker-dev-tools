<?php declare(strict_types=1);

namespace DDT\Tool;

use DDT\CLI;

class IpTool
{
    private $cli;
    private $config;

    public function __construct(CLI $cli, \SystemConfig $config)
    {
        $this->cli = $cli;
        $this->config = $config;
    }

    public function handle(): void
    {
        foreach($this->cli->getArgList() as $arg){
            switch($arg['name']){
                case 'help':
                    \Text::print(file_get_contents($this->config->getToolsPath('/help/ip.txt')));
                    \Script::die();
                    break;
            }
        }
    }
}
