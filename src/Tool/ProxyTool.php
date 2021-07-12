<?php declare(strict_types=1);

namespace DDT\Tool;

use DDT\CLI;

class ProxyTool extends Tool
{
    private $config;

    public function __construct(CLI $cli, \DDT\Config\SystemConfig $config)
    {
        parent::__construct('proxy', $cli);

        $this->config = $config;
    }

    public function getTitle(): string
    {
        return 'The Tool Title';
    }

    public function getShortDescription(): string
    {
        return 'A tool to control how the local proxy is configured and control whether it is running or not';
    }

    public function getDescription(): string
    {
        return "This tool will start a docker container and listen on DNS Port 53 and handle
        requests for your local development networks. Whilst pushing upstream all
        other requests it can't resolve to an online DNS server";
    }

    public function getHelp(): string
    {
        return 'The Help Template';
    }

    public function help(): void{}

    public function isRunning(): bool 
    {
        throw new \Exception('Proxy is not running');
    }

    public function handle(): void
    {
        foreach($this->cli->getArgList() as $arg){
            switch($arg['name']){
                case 'help':
                    \Text::print(file_get_contents($this->config->getToolsPath('/help/proxy.txt')));
                    \Script::die();
                    break;

                case 'start':
                    \Script::failure("TODO: implement {$arg['name']} functionality");

                    // if($network == true) $network = null;

                    // Text::print("{blu}Starting the Frontend Proxy:{end} ".$proxy->getDockerImage()."\n");

                    // $proxy->start($network);

                    // Text::print("{blu}Running Containers:{end}\n");
		            // Shell::passthru('docker ps');

                    // $cli->setArg('domains');
                    break;

                case 'stop';
                    \Script::failure("TODO: implement {$arg['name']} functionality");

                    // Text::print("{blu}Stopping the Frontend Proxy:{end} ".$proxy->getDockerImage()."\n");

                    // $proxy->stop();

                    // Text::print("{blu}Running Containers:{end}\n");
                    // Shell::passthru('docker ps');
                    break;
                
                case 'restart':
                    \Script::failure("TODO: implement {$arg['name']} functionality");
                    break;

                case 'logs':
                    \Script::failure("TODO: implement {$arg['name']} functionality");
                    $proxy->logs();
                    break;

                case 'logs-f':
                    \Script::failure("TODO: implement {$arg['name']} functionality");
                    $proxy->logsFollow();
                    break;

                case 'add':
                    \Script::failure("TODO: implement {$arg['name']} functionality");
                    // Text::print("{blu}Connecting to a new network '$network' to the proxy{end}\n");
                    // $proxy->addNetwork($network);
                    // Format::networkList($proxy->getNetworks());
                    break;

                case 'remove':
                    \Script::failure("TODO: implement {$arg['name']} functionality");
                    // Text::print("{blu}Disconnecting the network '$network' from the proxy{end}\n");
                    // $proxy->removeNetwork($network);
                    // Format::networkList($proxy->getNetworks());
                    break;

                case 'nginx-config':
                    \Script::failure("TODO: implement {$arg['name']} functionality");
                    //print($proxy->getConfig());

                    break;

                case 'status':
                    \Script::failure("TODO: implement {$arg['name']} functionality");
                    // Format::networkList($proxy->getListeningNetworks());
                    // Format::upstreamList($proxy->getUpstreams());
                    // if($format = $cli->getArg('networks')){
                    //     Format::networkList($proxy->getListeningNetworks(), $format);
                    // }
                    
                    break;

                case 'container-image':
                    \Script::failure("TODO: implement {$arg['name']} functionality");
                    // if($containerName = $cli->getArgWithVal('set-container-name')){
                    //     $proxy->setContainerName($containerName);
                    // }
                
                    // if($cli->hasArg('get-container-name')){
                    //     Text::print("Container: ".$proxy->getContainerName()."\n");
                    // }                
                    break;

                case 'docker-image':
                    \Script::failure("TODO: implement {$arg['name']} functionality");
                    // if($dockerImage = $cli->getArgWithVal('set-docker-image')){
                    //     $proxy->setDockerImage($dockerImage);
                    // }
                
                    // if($cli->hasArg('get-docker-image')){
                    //     Text::print("Docker Image: ".$proxy->getDockerImage()."\n");
                    // }
                    break;

                default:
                    \Script::failure("Unrecognised command '{$arg['name']}'");
                    break;
            }
        }
    }
}
