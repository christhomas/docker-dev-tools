<?php declare(strict_types=1);

namespace DDT\Tool;

use DDT\CLI;
use DDT\Config\ProxyConfig;
use DDT\Exceptions\Docker\DockerContainerNotFoundException;
use DDT\Network\Proxy;
use DDT\Text\Table;

class ProxyTool extends Tool
{
    /** @var ProxyConfig */
    private $config;

    /** @var Proxy */
    private $proxy;

    public function __construct(CLI $cli, ProxyConfig $config, Proxy $proxy)
    {
        parent::__construct('proxy', $cli);

        $this->config = $config;
        $this->proxy = $proxy;

        foreach([
            'start', 'stop', 'restart', 
            'logs', 'logs-f', 
            'add-network', 'remove-network', 
            'nginx-config', 'status', 
            'container-name', 'docker-image'
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
                "add-network <network-name>: Add a new network to a running proxy without needing to restart it\n".
                "remove-network <network-name>: Remove an existing network from the proxy container so it stops monitoring it\n".
                "\n".
                "{cyn}Configuration:{end}\n".
                "nginx-config: Output the raw /etc/nginx/conf.d/default.conf which is generated when containers start and stop\n".
                "status: Show the domains that the Nginx proxy will respond to\n".
                "container-name: Get/Set the name to give to this container. Pass a second parameter for the container name if you wish to set it\n".
                "docker-image: Get/Set the docker image name to run. Pass a second parameter for the docker image if you with to set it\n"
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
        try{
            $this->cli->print("{blu}Stopping the Frontend Proxy:{end} ".$this->dockerImage()."\n");
            $this->proxy->stop();

            // FIXME: perhaps this should call the docker object to do this
            $this->cli->print("{blu}Running Containers:{end}\n");
            $this->cli->passthru('docker ps');
        }catch(DockerContainerNotFoundException $e){
            $this->cli->failure("The Proxy Container is not running");
        }
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
    } 

    public function nginxConfig(?bool $colour=true)
    {
        if($this->proxy->isRunning()){
            $output = $this->proxy->getConfig();
            if($colour){
                $output = "\n{cyn}".$output."{end}\n\n";
            }
            $this->cli->print($output);
        }else{
            $this->cli->print('{red}Proxy is not running{end}');
        }
    }

    public function status()
    {
        $this->cli->print("{blu}Registered proxy services:{end}\n");
        $table = container(Table::class);

        $table->addRow([
            '{yel}Docker Network{end}',
            '{yel}Container{end}',
            '{yel}Host{end}',
            '{yel}Port{end}',
            '{yel}Path{end}',
            '{yel}Nginx Status{end}',
        ]);

        foreach($this->proxy->getNetworks(true) as $network){
            $containerList = $this->proxy->getContainersOnNetwork($network);

            if(empty($containerList)){
                $table->addRow([$network, "{yel}There are no containers{end}"]);
            }

            foreach($containerList as $container){
                $env = $this->proxy->getContainerProxyEnv($container['name']);
                $table->addRow([$network, $container['name'], $env['host'], $env['port'], $env['path'], $container['nginx_status']]);
            }
        }

        $this->cli->print($table->render());
    }

    public function containerName(?string $name=null)
    {
        if(empty($name)){
            return $this->config->getContainerName();
        }

        $this->cli->print("{blu}Setting ContainerName {end}: $name\n");
        if($this->config->setContainerName($name)){
            $this->cli->success("Succeeded to set container name to '$name'. Please restart proxy to see changes");
        }else{
            $this->cli->failure("Failed to set container name\n");
        }
    }

    public function dockerImage(?string $image=null)
    {
        if(empty($image)){
            return $this->config->getDockerImage();
        }

        $this->cli->print("{blu}Setting Docker Image{end}: $image\n");
        if($this->config->setDockerImage($image)){
            $this->cli->success("Succeeded to set docker image name to '$image'. Please restart proxy to see changes");
        }else{
            $this->cli->failure("Failed to set docker image\n");
        }
    }
}