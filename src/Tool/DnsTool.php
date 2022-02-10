<?php declare(strict_types=1);

namespace DDT\Tool;

use DDT\CLI;
use DDT\Config\DnsConfig;
use DDT\Config\IpConfig;
use DDT\Contract\DnsServiceInterface;
use DDT\Network\Address;
use DDT\Network\DnsMasq;
use DDT\Text\Table;
use DDT\Text\Text;

class DnsTool extends Tool
{
    /** @var DnsConfig */
    private $dnsConfig;

    /** @var IpConfig */
    private $ipConfig;

    /** @var DnsMasq */
    private $dnsMasq;
    
    /** @var DnsServiceInterface */
    private $dnsService;

    public function __construct(CLI $cli, DnsConfig $dnsConfig, IpConfig $ipConfig, DnsMasq $dnsMasq, DnsServiceInterface $dnsService)
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
    remove-domain=yourdomain.com: Remove a domain to the running DNS server
    ip=xxx.xxx.xxx.xxx: Use this ip address when configuring the server instead of the default one

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
        $this->dnsMasq->reload();
    }

    public function startCommand()
    {
        $this->cli->print("{blu}Starting:{end} DNS...\n");

        $this->dnsMasq->start();
        $this->enableCommand();

        $domainGroup = $this->dnsConfig->getDomainList();

        foreach($domainGroup as $ipAddress => $domainList){
            foreach($domainList as $domain){
                $this->dnsMasq->addDomain($domain, $ipAddress);
            }
        }

        $this->dnsMasq->reload();

        $this->pingCommand();
    }

    public function stopCommand()
    {
        $this->cli->print("{red}Stopping:{end} DNS...\n");

        $this->disableCommand();
        $this->dnsMasq->stop();

        $address = Address::instance('127.0.0.1');
        $address->ping();
        $this->cli->print((string)$address);

        $address = Address::instance('google.com');
        $address->ping();
        $this->cli->print((string)$address);
    }

    public function restartCommand(): void
    {
        $this->cli->print("{yel}Restarting:{end} DNS...\n");

        if($this->dnsMasq->isRunning()){
            $this->stopCommand();
            $this->startCommand();
        }else{
            $this->startCommand();
        }
    }

    public function logsCommand(?string $since=null): void
    {
        $this->dnsMasq->logs(false, $since);
    }

    public function logsFCommand(?string $since=null): void
    {
        $this->dnsMasq->logs(true, $since);
    }

    public function addDomainCommand(string $domain): void
    {
        $this->cli->sudo();

        $domain = Address::instance($domain);

        if($domain->hostname === null){
            throw new \Exception('The hostname was not interpreted correctly, if relevant; please use a hostname and not an ip address');
        }

        $ipAddress = $this->ipConfig->get();

        // TODO: support overriding the ip address through the command line argument --ip-address=x.x.x.x

        $this->cli->print("{blu}Adding domain:{end} '{yel}$domain->hostname{end}' with ip address '{yel}$ipAddress{end}' to Dns Resolver: ");
        
        if($this->dnsMasq->addDomain($domain->hostname, $ipAddress)){
            $this->dnsMasq->reload();

            $this->cli->silenceChannel('stdout', function(){
                $this->refreshCommand();
            });

            $domain = Address::instance($domain->hostname);
            if($domain->ping() === true){
                $this->cli->print("{grn}SUCCESS{end}\n");
            }else{
                $this->cli->print("{red}FAILURE{end}\n");
            }
        }
    }

    public function removeDomainCommand(string $domain): void
    {
        $this->cli->sudo();

        $domain = Address::instance($domain);

        if($domain->hostname === null){
            throw new \Exception('The hostname was not interpreted correctly, if relevant; please use a hostname and not an ip address');
        }

        $ipAddress = $this->ipConfig->get();

        // TODO: support overriding the ip address through the command line argument --ip-address=x.x.x.x

        $this->cli->print("{blu}Removing domain:{end} '{yel}$domain->hostname{end}' with ip address '{yel}$ipAddress{end}' from the Dns Resolver: ");
        
        if($this->dnsMasq->removeDomain($domain->hostname, $ipAddress)){
            $this->dnsMasq->reload();

            $this->cli->silenceChannel('stdout', function(){
                $this->refreshCommand();
            });

            $domain = Address::instance($domain->hostname);
            if($domain->ping() === false){
                $this->cli->print("{grn}SUCCESS{end}\n");
            }else{
                $this->cli->print("{red}FAILURE{end}\n");
            }
        }
    }

    public function ipCommand(?string $address=null): string
    {
        // TODO: what to do when you set a new ip address, here, should reconfigure everything with that new ip address?
        // NOTE: this could be quite a lot of changes in various aspects of the system that might be storing that ip address and using it locally
        $this->cli->debug("new ip address = '$address'");

        $list = $this->dnsService->getIpAddressList();

        return implode("\n", $list)."\n";
    }

    public function pingCommand()
    {
        $list = $this->dnsService->getIpAddressList();

        foreach($list as $ipAddress){
            $address = Address::instance($ipAddress);

            $address->ping();

            $this->cli->print((string)$address);
        }

        $address = Address::instance('127.0.0.1');
        $address->ping();
        $this->cli->print((string)$address);

        $address = Address::instance('google.com');
        $address->ping();
        $this->cli->print((string)$address);

        $domainGroup = $this->dnsConfig->getDomainList();
        foreach($domainGroup as $ipAddress => $domainList){
            foreach($domainList as $domain){
                $address = Address::instance($domain);
                $address->ping();
                $this->cli->print((string)$address);
            }
        }
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

    public function statusCommand(): string
    {
        $this->cli->print("{blu}Registered domains:{end}\n");

        $domainList = $this->dnsMasq->listDomains();

        $table = container('table');
        $table->setRightPadding(10);
        $table->addRow(['{yel}Domain{end}', '{yel}IP Address{end}']);

        $reply = [];
        foreach($domainList as $item){
            if(!array_key_exists($item['ip_address'], array_keys($reply))){
                $reply[$item['ip_address']] = true;
            }

            $table->addRow(["{wht}{$item['domain']}{end}", "{wht}{$item['ip_address']}{end}"]);
        }
        
        return $table->render(true);
    }

    public function listDevicesCommand() {
        $this->cli->print("{blu}List of all devices:{end}\n");
        
        foreach($this->dnsService->getHardwarePorts() as $index => $device){
            $this->cli->print($index+1 . " - ${device['name']} (dev: ${device['device']})\n");
        }
    }

    public function setDeviceCommand(string $device=null) {
        $this->cli->print("{blu}Set the device for DNS service:{end}\n");

        $list = $this->dnsService->getHardwarePorts();

        foreach($list as $index => $device){
            $this->cli->print($index+1 . " - ${device['name']} (dev: ${device['device']})\n");
        }

        $answer = (int)$this->cli->ask("Please enter the number of device to select: ", range(1,count($list)));

        if($answer < 1 || $answer > count($list)) {
            $this->cli->print("{red}Sorry but the device selected was not available (requested: $answer){end}\n");
        }else{
            $device = $list[$answer-1];
            $this->cli->print("You have requested device: {yel}{$device['name']} (dev: {$device['device']}){end}\n");
            $this->dnsConfig->setDevice($device['name'], $device['device']);
        }
    }

    public function removeDeviceCommand() {
        $this->dnsConfig->removeDevice();
    }
}
