<?php declare(strict_types=1);

namespace DDT\Tool;

use DDT\CLI;
use DDT\Distro\DistroDetect;
use DDT\Network\DNSMasq;
use DDT\Network\Network;

/**
 * Other useful DNS commands I found online that might be useful
 * MacOS:
 * - arp -ad = flush the arp (address resolution protocol) cache
 * - arp eu-west-1.s3.aws.develop = show information about a specific hostname
 */

class DnsTool extends Tool
{
    private $config;
    private $network;
    private $dns;

    public function __construct(CLI $cli, \DDT\Config\SystemConfig $config)
    {
    	parent::__construct('dns', $cli);

        $this->config = $config;
        $distro = DistroDetect::get();
        $this->network = new Network($distro);
        $this->dns = new DNSMasq($this->network);
    }

    public function getTitle(): string
    {
        return 'DNS Tool';
    }

    public function getShortDescription(): string
    {
        return 'A tool to manage the DNS configuration used when the system is running';
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
{cyn}Configuring IP Address and domains:{end}
    add-domain=yourdomain.com: Add a domain to the running DNS server
    remove-domain=yourdomain.com: Remove a domain to the running DNS server (see also --ip-address)
    set-ip=xxx.xxx.xxx.xxx: Use this ip address when configuring the server instead of the default one

{cyn}Toggling the DNS Server:{end}
    enable: Enable the DNS Server
    disable: Disable the DNS Server
    reset: Toggles the DNS Server as disabled then enabled as well as refreshing the dns cache

{cyn}Running of the DNS Server Container:{end}
    start: Setup the DNS servers and start the DNS container
    restart: Restart the DNS Container
    stop: Stop the DNS container
    
{cyn}Logging:{end}
    logs: View the logs from the DNS container
    logs-f: View and follow the logs from the DNS container

{cyn}Configuration:{end}
    status: View a list of all the domains that are registered with the DNS server
    container-name[=xxx]: Get the name of this container to use, if passed a parameter it will update the settings with that value
    docker-image[=xxx]: Get the docker image name to use, if passed a parameter it will update the settings with that value
OPTIONS;
    }

    public function getNotes(): string
    {
        return<<<NOTES
{yel}Enabling, disable, and resetting{end} the DNS Server doesn't change the running status of the 
DNS Container. It's just changing your system configuration from using the DNS Server or 
going back to your default computer DNS settings. It's useful when you need to quickly toggle back 
to the system defaults because the DNS Server might interfere with running a VPN. So you can quickly 
disable it, do your other work. Then re-enable it when you need to get back to working with the 
development environment. It's like a soft reset of your DNS configuration so you can temporarily 
do something.

{yel}Starting, Stopping, and Restarting{end} implies also {yel}enabling and disabling{end} the 
DNS Server like explained above. However it does the extra step of Starting or Stopping the 
docker container as well. So it's more like a hard reset.
NOTES;
    }

    public function enable(): void
    {
        \Text::print("{grn}Enabling:{end} DNS...\n");
        $this->dns->enable();
    }

    public function disable(): void
    {
        \Text::print("{red}Disabling:{end} DNS...\n");
        $this->dns->disable();
    }

    public function refresh(): void
    {
        \Text::print("{yel}Refreshing:{end} DNS...\n");
        $this->dns->refresh();
    }

    public function start(): bool
    {
        \Text::print("{blu}Starting:{end} DNS...\n");

        $this->dns->start();

        return true;

        // $dns->start();
        // $network->enableDNS();

        // Format::ping($alias->ping('127.0.0.1'));
        // Format::ping($alias->ping('google.com'));

        // // Configure all the domains to be resolved on this computer
        // $domainList = $dns->listDomains();
        // foreach($domainList as $domain){
        //     $dns->addDomain($alias->get(), $domain['domain']);
        //     Format::ping($alias->ping($domain['domain'], $domain['ip_address']));
        // }
    }

    public function stop(): bool
    {
        \Text::print("{red}Stopping:{end} DNS...\n");

        $this->dns->stop();

        return false;

        // $network->disableDNS();

        // Format::ping($alias->ping('127.0.0.1'));
        // Format::ping($alias->ping('google.com'));

        // $domainList = $dns->listDomains();
        // foreach($domainList as $domain){
        //     Format::ping($alias->ping($domain['domain'], $domain['ip_address']));
        // }

        // $dns->stop();
    }

    public function restart(): void
    {
        \Text::print("{yel}Restarting:{end} DNS...\n");

        if($this->dns->isRunning()){
            $this->stop();
            $this->start();
        }else{
            $this->start();
        }
    }

    public function logs(): void
    {
        \Text::print("{yel}TODO: logs{end}\n");
        $this->dns->logs();
    }

    public function logsF(): void
    {
        \Text::print("{yel}TODO: logs follow{end}\n");
        $this->dns->logs(true);
    }

    public function addDomain(): void
    {
        \Text::print("{yel}TODO: addDomain{end}\n");
        // $ipAddress = $cli->getArgWithVal('ip-address', $alias->get());

        // if($ipAddress !== $alias->get()){
        //     Text::print("{blu}Overriding IP Alias:{end} '{yel}" . $alias->get() . "{end}' with custom IP Address '{yel}$ipAddress{end}'\n\n");
        // }

        // $dns->addDomain($ipAddress, $domain);

        // Format::ping($alias->ping('google.com'));
        // Format::ping($alias->ping($domain, $ipAddress));
    }

    public function removeDomain(): void
    {
        \Text::print("{yel}TODO: removeDomain{end}\n");
        // $dns->removeDomain($domain);

        // Format::ping($alias->ping('google.com'));
        // Format::ping($alias->ping($domain));
    }

    public function setIp(): void
    {
        \Text::print("{yel}TODO: setIp{end}\n");
    }

    public function containerName(): void
    {
        \Text::print("{yel}TODO: containerName{end}\n");
        // set: $dns->setContainerName($containerName);
        // get: Text::print("Container: ".$dns->getContainerName()."\n");
    }

    public function dockerImage(): void
    {
        \Text::print("{yel}TODO: dockerImage{end}\n");
        // set: $dns->setDockerImage($dockerImage);
        // get: Text::print("Docker Image: ".$dns->getDockerImage()."\n");
    }

    public function status(): void
    {
        \Text::print("{yel}TODO: Show status information here{end}\n");
        // Text::print("{blu}Domains that are registered in the dns container:{end}\n");

        // $domainList = $dns->listDomains(true);

        // $table = new TextTable();
        // $table->setRightPadding(10);
        // $table->addRow(['Domain', 'IP Address']);
        // foreach($domainList as $domain){
        //     $table->addRow([$domain['domain'], $domain['ip_address']]);
        // }
        // $table->render();
    }
}
