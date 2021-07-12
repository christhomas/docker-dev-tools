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
        return 'The Tool Title';
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

    public function getHelp(): string
    {
        return 'The Help Template';
    }

    protected function help(): void
    {
        \Text::print(file_get_contents($this->config->getToolsPath("/help/{$this->name}.txt")));
        \Script::die();
    }

    private function enable(): void
    {
        \Text::print("{grn}Enabling:{end} DNS...\n");
        $this->dns->enable();
    }

    private function disable(): void
    {
        \Text::print("{red}Disabling:{end} DNS...\n");
        $this->dns->disable();
    }

    private function refresh(): void
    {
        \Text::print("{yel}Refreshing:{end} DNS...\n");
        $this->dns->refresh();
    }

    private function start(): bool
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

    private function stop(): bool
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

    private function restart(): void
    {
        \Text::print("{yel}Restarting:{end} DNS...\n");

        if($this->dns->isRunning()){
            $this->stop();
            $this->start();
        }else{
            $this->start();
        }
    }

    private function logs(): void
    {
        \Text::print("{yel}TODO: logs{end}\n");
        $this->dns->logs();
    }

    private function logsF(): void
    {
        \Text::print("{yel}TODO: logs follow{end}\n");
        $this->dns->logs(true);
    }

    private function addDomain(): void
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

    private function removeDomain(): void
    {
        \Text::print("{yel}TODO: removeDomain{end}\n");
        // $dns->removeDomain($domain);

        // Format::ping($alias->ping('google.com'));
        // Format::ping($alias->ping($domain));
    }

    private function setIp(): void
    {
        \Text::print("{yel}TODO: setIp{end}\n");
    }

    private function containerName(): void
    {
        \Text::print("{yel}TODO: containerName{end}\n");
        // set: $dns->setContainerName($containerName);
        // get: Text::print("Container: ".$dns->getContainerName()."\n");
    }

    private function dockerImage(): void
    {
        \Text::print("{yel}TODO: dockerImage{end}\n");
        // set: $dns->setDockerImage($dockerImage);
        // get: Text::print("Docker Image: ".$dns->getDockerImage()."\n");
    }

    private function status(): void
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
