<?php declare(strict_types=1);

namespace DDT\Tool;

use DDT\CLI;
use DDT\Config\SystemConfig;

class ProxyTool extends Tool
{
    /** @var SystemConfig */
    private $config;

    /** @var \Proxy */
    private $proxy;

    public function __construct(CLI $cli, SystemConfig $config)
    {
        parent::__construct('proxy', $cli);

        $this->config = $config;
        $docker = new \Docker($this->config);
        $this->proxy = new \Proxy($this->config, $docker);
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

    public function start()
    {
        \Text::print("{blu}Starting the Frontend Proxy:{end} ".$this->proxy->getDockerImage()."\n");
        $this->proxy->start();

        \Text::print("{blu}Running Containers:{end}\n");
        // FIXME: this should call the docker object to do this
        \Shell::passthru('docker ps');
    }

    public function stop()
    {
        \Text::print("{blu}Stopping the Frontend Proxy:{end} ".$this->proxy->getDockerImage()."\n");
        $this->proxy->stop();

        \Text::print("{blu}Running Containers:{end}\n");
        \Shell::passthru('docker ps');
    }

    public function restart()
    {
        $this->stop();
        $this->start();
    }

    public function logs()
    {
        $docker = new \Docker($this->config);
        $proxy = new \Proxy($this->config, $docker);
        $proxy->logs();
    }

    public function logsF()
    {
        $docker = new \Docker($this->config);
        $proxy = new \Proxy($this->config, $docker);
        $proxy->logsFollow();
    }

    public function add()
    {
        \Script::failure("TODO: implement ".__METHOD__." functionality");
        // Text::print("{blu}Connecting to a new network '$network' to the proxy{end}\n");
        // $proxy->addNetwork($network);
        // Format::networkList($proxy->getNetworks());
    }

    public function remove()
    {
        \Script::failure("TODO: implement ".__METHOD__." functionality");
        // Text::print("{blu}Disconnecting the network '$network' from the proxy{end}\n");
        // $proxy->removeNetwork($network);
        // Format::networkList($proxy->getNetworks());
    } 

    public function nginxConfig()
    {
        \Script::failure("TODO: implement ".__METHOD__." functionality");
        //print($proxy->getConfig());
    }

    public function status()
    {
        \Script::failure("TODO: implement ".__METHOD__." functionality");
        // Format::networkList($proxy->getListeningNetworks());
        // Format::upstreamList($proxy->getUpstreams());
        // if($format = $cli->getArg('networks')){
        //     Format::networkList($proxy->getListeningNetworks(), $format);
        // }
    }

    public function containerImage()
    {
        \Script::failure("TODO: implement ".__METHOD__." functionality");
        // if($containerName = $cli->getArgWithVal('set-container-name')){
        //     $proxy->setContainerName($containerName);
        // }
    
        // if($cli->hasArg('get-container-name')){
        //     Text::print("Container: ".$proxy->getContainerName()."\n");
        // }                
    }

    public function dockerImage()
    {
        \Script::failure("TODO: implement ".__METHOD__." functionality");
        // if($dockerImage = $cli->getArgWithVal('set-docker-image')){
        //     $proxy->setDockerImage($dockerImage);
        // }
    
        // if($cli->hasArg('get-docker-image')){
        //     Text::print("Docker Image: ".$proxy->getDockerImage()."\n");
        // }
    }
}