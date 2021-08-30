<?php declare(strict_types=1);

namespace DDT\Tool;

use DDT\CLI;
use DDT\Docker\Docker;

class DockerTool extends Tool
{
    /** @var Docker $docker */
    private $docker;
    
    public function __construct(CLI $cli, Docker $docker)
    {
        parent::__construct('docker', $cli);

        $this->docker = $docker;
    }

    public function getTitle(): string
    {
        return 'Docker Helper';
    }

    public function getShortDescription(): string
    {
        return 'A tool to interact with docker enhanced by the dev tools to provide extra functionality';
    }

    public function getDescription(): string
    {
        return<<<DESCRIPTION
        This tool will manage the configured docker execution profiles that you can use in other tools.
        Primarily the tool was created for the purpose of wrapping up and simplifying the ability to
        execute docker commands on other docker servers hosted elsewhere. 
        
        These profiles contain connection information to those remote docker profiles and make it 
        easy to integrate working with those remote servers into other tools without spreading
        the connection information into various places throughout your custom toolsets
DESCRIPTION;
    }

    public function getOptions(): string
    {
        return<<<OPTIONS
        {cyn}Managing Profiles{end}
        --add-profile=xxx: The name of the profile to create
        --remove-profile=xxx: The name of the profile to remove
        --list-profile(s): List all the registered profiles

        --host=xxx: The host of the docker server (or IP Address)
        --port=xxx: The port, when using TLS, it must be 2376
        --tlscacert=xxx: The filename of this tls cacert (cacert, not cert)
        --tlscert=xxx: The filename of the tls cert
        --tlskey=xxx: The filename of the tls key        
              
        {cyn}Using Profiles{end}
        --get-json=xxx: To obtain a profile as a JSON string
        --profile=xxx: To execute a command using this profile (all following arguments are sent directly to docker executable without modification
OPTIONS;
    }

    public function getNotes(): string
    {
        return<<<NOTES
        The parameter {yel}--add-profile{end} depends on: {yel}host, port, tlscacert, tlscert, tlskey{end} options
        and unfortunately you can't create a profile without all of those paraameters at the moment
        
        If you don't pass a profile to execute under, it'll default to your local docker server. Which means you can use this
        tool as a wrapper and optionally pass commands to various dockers by just adjusting the command parameters and 
        adding the {yel}--profile=staging{end} or not
NOTES;
    }

    public function getExamples(): string
    {
        $entrypoint = $this->cli->getScript(false) . ' ' . $this->getName();

        return<<<EXAMPLES
        {yel}Usage Examples: {end}
        $entrypoint --add-profile=staging --host=mycompany.com --port=2376 --tlscacert=cacert.pem --tlscert=cert.pem --tlskey=key.pem
        $entrypoint --remove-profile=staging
        $entrypoint --get-profile=staging
        $entrypoint --list-profiles
        $entrypoint --profile=staging exec -it phpfpm sh
EXAMPLES;
    }

    public function statusCommand()
    {
        var_dump($this->docker->listProfile());
        $this->cli->failure("showing state");
    }

    public function addProfile()
    {
        $this->cli->failure("{red}TODO: Implement: ".__METHOD__."\n");

        /*if(($profile = $cli->getArgWithVal('add-profile')) !== null){
            $host       = $cli->getArgWithVal('host');
            $port       = $cli->getArgWithVal('port');
            $tlscacert  = $cli->getArgWithVal('tlscacert');
            $tlscert    = $cli->getArgWithVal('tlscert');
            $tlskey     = $cli->getArgWithVal('tlskey');
        
            if($docker->addProfile($profile, $host, (int)$port, $tlscacert, $tlscert, $tlskey)){
                $this->cli->success("Profile '$profile' written successfully");
            }else{
                $this->cli->failure("Profile '$profile' did not write successfully");
            }
        }*/
    }

    public function removeProfile()
    {
        $this->cli->failure("{red}TODO: Implement: ".__METHOD__."\n");

        /*if(($profile = $cli->getArgWithVal('remove-profile')) !== null){
            if($docker->removeProfile($profile)){
                $this->cli->success("Profile '$profile' was removed successfully");
            }else{
                $this->cli->failure("Profile '$profile' did not remove successfully");
            }
        }*/        
    }

    public function listProfile()
    {
        $this->cli->failure("{red}TODO: Implement: ".__METHOD__."\n");
        
        /*if($cli->hasArg(['list-profile', 'list-profiles'])){
            $profileList = $docker->listProfiles();
        
            Text::print("{blu}Docker Profiles:{end}\n");
            foreach(array_keys($profileList) as $name){
                Text::print(" - $name\n");
            }
            if(empty($profileList)){
                Text::print("There are no registered docker profiles\n");
            }
        
            exit(0);
        }*/
    }

    public function getJson(string $profile)
    {
        $this->cli->failure("{red}TODO: Implement: ".__METHOD__."\n");

        /*if(($profile = $cli->getArgWithVal('get-json')) !== null){
            $json = (string)$docker->getProfile($profile);
            if($json !== null){
                die($json."\n");
            }else{
                $this->cli->failure("Profile '$profile' was not found or could not be decoded");
            }
        }*/
    }

    public function useProfile()
    {
        $this->cli->failure("{red}TODO: Implement: ".__METHOD__."\n");

        /*if(($profile = $cli->getArgWithVal('profile')) !== null){
            if($docker->useProfile($profile) === false){
                $this->cli->failure("Profile '$profile' did not exist");
            }
        }*/       
    }

    public function runDocker()
    {
        $this->cli->failure("{red}TODO: Implement: ".__METHOD__."\n");

        /*$args = $cli->getArgList(true);
        unset($args['--profile']);
        
        try{
            $docker->passthru(implode(" ", $args));
        }catch(Exception $e){
            exit(1);
        }*/       
    }
}
