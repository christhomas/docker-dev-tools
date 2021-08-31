<?php declare(strict_types=1);

namespace DDT\Tool;

use DDT\CLI;
use DDT\Config\DnsConfig;
use DDT\Config\IpConfig;
use DDT\Config\SystemConfig;
use DDT\Contract\DnsServiceInterface;
use DDT\Network\DNSMasq;
use DDT\Text\Table;
use DDT\Text\Text;

class DnsTool extends Tool
{
    private $dnsConfig;
    private $ipConfig;

    private $dnsMasq;
    private $dnsService;

    public function __construct(CLI $cli, DnsConfig $dnsConfig, IpConfig $ipConfig, DNSMasq $dnsMasq, DnsServiceInterface $dnsService)
    {
    	parent::__construct('dns', $cli);

        $this->dnsConfig = $dnsConfig;
        $this->ipConfig = $ipConfig;

        $this->dnsMasq = $dnsMasq;
        $this->dnsService = $dnsService;
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
    refresh: Toggles the DNS Server as disabled then enabled as well as refreshing the dns cache

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

    public function enableCommand()
    {
        $this->cli->print("{grn}Enabling:{end} DNS...\n");

        $dnsIpAddress = $this->ipConfig->get();

        if(empty($dnsIpAddress)){
            throw new \Exception('The system configuration had no usable ip address for this system configured');
        }

        $this->dnsService->enable($dnsIpAddress);
    }

    public function disableCommand()
    {
        $this->cli->print("{red}Disabling:{end} DNS...\n");
        $this->dnsService->disable();
    }

    public function refreshCommand()
    {
        $this->cli->print("{yel}Refreshing:{end} DNS...\n");
        
        $this->disableCommand();
        $this->enableCommand();
    }

    public function startCommand()
    {
        $this->cli->print("{blu}Starting:{end} DNS...\n");

        $this->dnsMasq->start();
        $this->enableCommand();

        // Format::ping($alias->ping('127.0.0.1'));
        // Format::ping($alias->ping('google.com'));

        // // Configure all the domains to be resolved on this computer
        // $domainList = $dns->listDomains();
        // foreach($domainList as $domain){
        //     $dns->addDomain($alias->get(), $domain['domain']);
        //     Format::ping($alias->ping($domain['domain'], $domain['ip_address']));
        // }
    }

    public function stopCommand()
    {
        $this->cli->print("{red}Stopping:{end} DNS...\n");

        $this->disableCommand();
        $this->dnsMasq->stop();

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
        $this->cli->print("{yel}Restarting:{end} DNS...\n");

        if($this->dnsMasq->isRunning()){
            $this->stopCommand();
            $this->startCommand();
        }else{
            $this->startCommand();
        }
    }

    public function logsCommand(): void
    {
        $this->dnsMasq->logs();
    }

    public function logsFCommand(): void
    {
        $this->dnsMasq->logs(true);
    }

    public function addDomainCommand(): void
    {
        $this->cli->print("{yel}TODO: addDomain{end}\n");
        // $ipAddress = $cli->getArgWithVal('ip-address', $alias->get());

        // if($ipAddress !== $alias->get()){
        //     $this->cli->print("{blu}Overriding IP Alias:{end} '{yel}" . $alias->get() . "{end}' with custom IP Address '{yel}$ipAddress{end}'\n\n");
        // }

        // $dns->addDomain($ipAddress, $domain);

        // Format::ping($alias->ping('google.com'));
        // Format::ping($alias->ping($domain, $ipAddress));
    }

    public function removeDomainCommand(): void
    {
        $this->cli->print("{yel}TODO: removeDomain{end}\n");
        // $dns->removeDomain($domain);

        // Format::ping($alias->ping('google.com'));
        // Format::ping($alias->ping($domain));
    }

    public function setIp(): void
    {
        $this->cli->print("{yel}TODO: setIp{end}\n");
    }

    public function containerNameCommand(): string
    {
        $containerName = $this->cli->shiftArg();

        if(empty($containerName)){
            return $this->dnsConfig->getContainerName();
        }else{
            $containerName = $containerName['name'];
            return $this->dnsConfig->setContainerName($containerName);
        }
    }

    public function dockerImageCommand(): string
    {
        $dockerImage = $this->cli->shiftArg();

        if(empty($dockerImage)){
            return $this->dnsConfig->getDockerImage();
        }else{
            $dockerImage = $dockerImage['name'];
            return $this->dnsConfig->setDockerImage($dockerImage);
        }
    }

    public function statusCommand(): void
    {
        $this->cli->print("{yel}TODO: Show status information here{end}\n");

        $domainList = $this->dnsMasq->listDomains();
        var_dump($domainList);

        // $this->cli->print("{blu}Domains that are registered in the dns container:{end}\n");

        // $domainList = $dns->listDomains(true);

        // $table = new Table(new Text());
        // $table->setRightPadding(10);
        // $table->addRow(['Domain', 'IP Address']);
        // foreach($domainList as $domain){
        //     $table->addRow([$domain['domain'], $domain['ip_address']]);
        // }
        // $table->render();
    }
}
