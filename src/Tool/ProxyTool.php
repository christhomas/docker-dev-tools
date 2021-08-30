<?php declare(strict_types=1);

namespace DDT\Tool;

use DDT\CLI;
use DDT\Config\SystemConfig;
use DDT\Docker\Docker;
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
    }

    public function getTitle(): string
    {
        return 'Frontend Proxy Tool';
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

    public function getOptions(): string
    {
        return<<<OPTIONS
{cyn}Running of the NGINX Front End Proxy Container:{end}
start: Run the Nginx proxy, with an optional assignment for the network name to use
stop: Stop the Nginx proxy
restart: Restart the proxy

{cyn}Logging:{end}
logs: View the logs from the Nginx proxy container
logs-f: View and follow the logs from the Nginx proxy container

{cyn}Network Configuration:{end}
add=XXX: Add a new network to a running proxy without needing to restart it
remove=XXX: Remove an existing network from the proxy container so it stops monitoring it

{cyn}Configuration:{end}
nginx-config: Output the raw /etc/nginx/conf.d/default.conf which is generated when containers start and stop
status: Show the domains that the Nginx proxy will respond to
container-name: Get/Set the name to give to this container
docker-image: Get/Set the docker image name to run
OPTIONS;
    }

    public function getExamples(): string
    {
        return implode("\n", [
            "{yel}Usage Example:{end} ddt proxy logs-f {grn}- follow the log output for the proxy{end}",
            "{yel}Usage Example:{end} ddt proxy start {grn}- start the proxy{end}"
        ]);
    }

    public function isRunning(): bool 
    {
        throw new \Exception('Proxy is not running');
    }

    public function startCommand()
    {
        $this->cli->print("{blu}Starting the Frontend Proxy:{end} ".$this->proxy->getDockerImage()."\n");
        $this->proxy->start();

        // FIXME: perhaps this should call the docker object to do this
        $this->cli->print("{blu}Running Containers:{end}\n");
        $this->cli->passthru('docker ps');
    }

    public function stopCommand()
    {
        $this->cli->print("{blu}Stopping the Frontend Proxy:{end} ".$this->proxy->getDockerImage()."\n");
        $this->proxy->stop();

        // FIXME: perhaps this should call the docker object to do this
        $this->cli->print("{blu}Running Containers:{end}\n");
        $this->cli->passthru('docker ps');
    }

    public function restartCommand()
    {
        $this->stopCommand();
        $this->startCommand();
    }

    public function logsCommand()
    {
        $this->proxy->logs();
    }

    public function logsFCommand()
    {
        $this->proxy->logsFollow();
    }

    public function addNetworkCommand(string $network)
    {
        if(empty($network)){
            throw new \Exception('Network must be a non-empty string');
        }

        $network = $network;

        $this->cli->print("{blu}Connecting to a new network '$network' to the proxy container '{$this->proxy->getContainerName()}'{end}\n");

        $this->proxy->addNetwork($network);
        $this->statusCommand();
        // Format::networkList($proxy->getNetworks());
    }

    public function removeNetworkCommand(string $network)
    {
        if(empty($network)){
            throw new \Exception('Network must be a non-empty string');
        }

        $network = $network;

        $this->cli->print("{blu}Disconnecting the network '$network' from the proxy container '{$this->proxy->getContainerName()}'{end}\n");

        $this->proxy->removeNetwork($network);
        $this->statusCommand();
        // Format::networkList($proxy->getNetworks());
    } 

    public function nginxConfigCommand()
    {
        if($this->proxy->isRunning()){
            $this->cli->print('{cyan}'.$this->proxy->getConfig().'{end}');
        }else{
            $this->cli->print('{red}Proxy is not running{end}');
        }
    }

    public function statusCommand()
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

    public function containerNameCommand(?string $name=null)
    {
        if(empty($name)){
            return $this->proxy->getContainerName();
        }

        $this->proxy->setContainerName($name);
    }

    public function dockerImageCommand(?string $image=null)
    {
        if(empty($image)){
            return $this->proxy->getDockerImage();
        }

        $this->proxy->setDockerImage($image);
    }
}