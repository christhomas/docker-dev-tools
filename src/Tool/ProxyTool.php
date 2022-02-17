<?php declare(strict_types=1);

namespace DDT\Tool;

use DDT\CLI;
use DDT\Config\SystemConfig;
use DDT\Network\Proxy;

class ProxyTool extends Tool
{
    /** @var SystemConfig */
    private $config;

    /** @var Proxy */
    private $proxy;

    public function __construct(CLI $cli, SystemConfig $config, Proxy $proxy)
    {
        parent::__construct('proxy', $cli);

        $this->config = $config;
        $this->proxy = $proxy;

        foreach([
            'start', 'stop', 'restart', 'logs', 'logsF', 'addNetwork', 
            'removeNetwork', 'nginxConfig', 'status', 'containerName', 'dockerImage'
        ] as $command){
            $this->setToolCommand($command);
        }
    }

    public function getToolMetadata(): array
    {
        return [
            'title' => 'Frontend Proxy Tool',
            'short_description' => 'A tool to control how the local proxy is configured and control whether it is running or not',
            'description' => trim(
                "This tool will start a docker container and listen on DNS Port 53 and handle\n".
                "requests for your local development networks. Whilst pushing upstream all\n".
                "other requests it can't resolve to an online DNS server\n"
            ),
            'options' => trim(
                "{cyn}Running of the NGINX Front End Proxy Container:{end}\n".
                "start: Run the Nginx proxy, with an optional assignment for the network name to use\n".
                "stop: Stop the Nginx proxy\n".
                "restart: Restart the proxy\n".
                "\n".
                "{cyn}Logging:{end}\n".
                "logs: View the logs from the Nginx proxy container\n".
                "logs-f: View and follow the logs from the Nginx proxy container\n".
                "\n".
                "{cyn}Network Configuration:{end}\n".
                "add-network=XXX: Add a new network to a running proxy without needing to restart it\n".
                "remove-network=XXX: Remove an existing network from the proxy container so it stops monitoring it\n".
                "\n".
                "{cyn}Configuration:{end}\n".
                "nginx-config: Output the raw /etc/nginx/conf.d/default.conf which is generated when containers start and stop\n".
                "status: Show the domains that the Nginx proxy will respond to\n".
                "container-name: Get/Set the name to give to this container\n".
                "docker-image: Get/Set the docker image name to run\n"
            ),
            'examples' => trim(
                "{yel}Usage Example:{end} ddt proxy logs-f {grn}- follow the log output for the proxy{end}\n".
                "{yel}Usage Example:{end} ddt proxy start {grn}- start the proxy{end}\n"
            )
        ];
    }

    public function isRunning(): bool 
    {
        throw new \Exception('Proxy is not running');
    }

    public function start()
    {
        $this->cli->print("{blu}Starting the Frontend Proxy:{end} ".$this->dockerImage()."\n");
        $this->proxy->start();

        // FIXME: perhaps this should call the docker object to do this
        $this->cli->print("{blu}Running Containers:{end}\n");
        $this->cli->passthru('docker ps');
    }

    public function stop()
    {
        $this->cli->print("{blu}Stopping the Frontend Proxy:{end} ".$this->dockerImage()."\n");
        $this->proxy->stop();

        // FIXME: perhaps this should call the docker object to do this
        $this->cli->print("{blu}Running Containers:{end}\n");
        $this->cli->passthru('docker ps');
    }

    public function restart()
    {
        $this->stop();
        $this->start();
    }

    public function logs(?string $since=null)
    {
        $this->proxy->logs(false, $since);
    }

    public function logsF(?string $since=null)
    {
        $this->proxy->logs(true, $since);
    }

    public function addNetwork(string $network)
    {
        if(empty($network)){
            throw new \Exception('Network must be a non-empty string');
        }

        $network = $network;

        $this->cli->print("{blu}Connecting to a new network '$network' to the proxy container '{$this->containerName()}'{end}\n");

        $this->proxy->addNetwork($network);
        $this->status();
        // Format::networkList($proxy->getNetworks());
    }

    public function removeNetwork(string $network)
    {
        if(empty($network)){
            throw new \Exception('Network must be a non-empty string');
        }

        $network = $network;

        $this->cli->print("{blu}Disconnecting the network '$network' from the proxy container '{$this->containerName()}'{end}\n");

        $this->proxy->removeNetwork($network);
        $this->status();
        // Format::networkList($proxy->getNetworks());
    } 

    public function nginxConfig()
    {
        if($this->proxy->isRunning()){
            $this->cli->print("\n{cyan}".$this->proxy->getConfig()."{end}\n\n");
        }else{
            $this->cli->print('{red}Proxy is not running{end}');
        }
    }

    public function status()
    {
        // Just for now, dump this list like this
        // TODO: what are listning networks?
        // TODO: perhaps I meant configured networks and active networks
        var_dump($this->proxy->getListeningNetworks());

        // 1. get a list of configured networks
        // 2. get the list of active networks 
        // 3. show a list of both configured and active networks, with their configured and active statuses
        // 4. show a list of upstreams the proxy has configured

        // old code, should delete it and do the above instead
        // Format::networkList($proxy->getListeningNetworks());
        // Format::upstreamList($proxy->getUpstreams());
        // if($format = $cli->getArg('networks')){
        //     Format::networkList($proxy->getListeningNetworks(), $format);
        // }
    }

    public function containerName(?string $name=null)
    {
        if(empty($name)){
            return $this->proxy->getContainerName();
        }

        $this->proxy->setContainerName($name);
    }

    public function dockerImage(?string $image=null)
    {
        if(empty($image)){
            return $this->proxy->getDockerImage();
        }

        $this->proxy->setDockerImage($image);
    }
}