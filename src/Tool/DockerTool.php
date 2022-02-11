<?php declare(strict_types=1);

namespace DDT\Tool;

use DDT\CLI;
use DDT\Config\DockerConfig;
use DDT\Docker\Docker;
use DDT\Docker\DockerRunProfile;

class DockerTool extends Tool
{
    /** @var Docker $docker */
    private $docker;

    /** @var DockerConfig $config */
    private $config;
    
    public function __construct(CLI $cli, Docker $docker, DockerConfig $config)
    {
        parent::__construct('docker', $cli);

        $this->docker = $docker;
        $this->config = $config;
    }

    public function getToolMetadata(): array
    {
        $entrypoint = $this->cli->getScript(false) . ' ' . $this->getToolName();

        return [
            'title' => 'Docker Helper',
            'short_description' => 'A tool to interact with docker enhanced by the dev tools to provide extra functionality',
            'description' => trim(
                "This tool will manage the configured docker execution profiles that you can use in other tools.\n".
                "Primarily the tool was created for the purpose of wrapping up and simplifying the ability to\n".
                "execute docker commands on other docker servers hosted elsewhere.\n".
                "\n".
                "These profiles contain connection information to those remote docker profiles and make it\n".
                "easy to integrate working with those remote servers into other tools without spreading\n".
                "the connection information into various places throughout your custom toolsets\n"
            ),
            'options' => trim(
                "{cyn}Managing Profiles{end}\n".
                "--add-profile=xxx: The name of the profile to create\n".
                "--remove-profile=xxx: The name of the profile to remove\n".
                "--list-profile(s): List all the registered profiles\n".
                "\n".
                "--host=xxx: The host of the docker server (or IP Address)\n".
                "--port=xxx: The port, when using TLS, it must be 2376\n".
                "--tlscacert=xxx: The filename of this tls cacert (cacert, not cert)\n".
                "--tlscert=xxx: The filename of the tls cert\n".
                "--tlskey=xxx: The filename of the tls key\n".
                "\n".   
                "{cyn}Using Profiles{end}\n".
                "--get-json=xxx: To obtain a profile as a JSON string\n".
                "--profile=xxx: To execute a command using this profile (all following arguments are sent directly to docker executable without modification\n"
            ),
            'notes' => trim(
                "The parameter {yel}--add-profile{end} depends on: {yel}name, host, port, tlscacert, tlscert, tlskey{end} options\n".
                "and unfortunately you can't create a profile without all of those paraameters at the moment\n".
                "\n".
                "If you don't pass a profile to execute under, it'll default to your local docker server. Which means you can use this\n".
                "tool as a wrapper and optionally pass commands to various dockers by just adjusting the command parameters and\n".
                "adding the {yel}--profile=staging{end} or not\n"
            ),
            'examples' => trim(
                "{yel}Usage Examples: {end}\n".
                "$entrypoint profile --name=staging exec -it phpfpm sh\n".
                "$entrypoint add-profile --name=staging --host=mycompany.com --port=2376 --tlscacert=cacert.pem --tlscert=cert.pem --tlskey=key.pem\n".
                "$entrypoint remove-profile --name=staging\n".
                "$entrypoint get-profile --name=staging\n".
                "$entrypoint list-profile\n"
            )
        ];
    }

    public function addProfileCommand(string $name, string $host, int $port, string $tlscacert, string $tlscert, string $tlskey)
    {
        $this->cli->print("{blu}Creating new Docker Run Profile:{end}\n\n");
        $this->cli->print(" - name: '$name'\n");
        $this->cli->print(" - host: '$host'\n");
        $this->cli->print(" - port: '$port'\n");
        $this->cli->print(" - tlscacert: '$tlscacert'\n");
        $this->cli->print(" - tlscert: '$tlscert'\n");
        $this->cli->print(" - tlskey: '$tlskey'\n");

        $profile = new DockerRunProfile($name, $host, $port, $tlscacert, $tlscert, $tlskey);
        
        if($this->config->writeProfile($profile)){
            $this->cli->success("\nDocker Run Profile '$name' written successfully\n");
        }else{
            $this->cli->failure("\nDocker Run Profile '$name' did not write successfully\n");
        }
    }

    public function removeProfileCommand(string $name)
    {
        $this->cli->print("{blu}Removing Docker Run Profile:{end} '$name'\n\n");

        if($this->config->deleteProfile($name)){
            $this->cli->success("\nDocker Run Profile '$name' removed successfully\n");
        }else{
            $this->cli->failure("\nDocker Run Profile '$name' could not be removed successfully\n");
        }
    }

    public function listProfileCommand()
    {
        $this->cli->print("{blu}Listing Docker Run Profiles{end}\n\n");

        $list = $this->config->listProfile();

        foreach($list as $profile){
            $data = $profile->get();

            $this->cli->print("{blu}Profile:{end} {$data['name']}\n");
            $this->cli->print(" - host: '{$data['host']}'\n");
            $this->cli->print(" - port: '{$data['port']}'\n");
            $this->cli->print(" - tlscacert: '{$data['tlscacert']}'\n");
            $this->cli->print(" - tlscert: '{$data['tlscert']}'\n");
            $this->cli->print(" - tlskey: '{$data['tlskey']}'\n\n");
        }

        if(empty($list)){
            $this->cli->print("There are no registered Docker Run Profiles\n");
        }
    }

    public function profileCommand(string $name)
    {
        $profile = $this->config->readProfile($name);
        $args = $this->cli->getArgList();
        array_shift($args);

        foreach($args as $index => $item){
            $args[$index] = $item['value'] !== null ? "{$item['name']}={$item['value']}" : $item['name'];
        }

        $commandLine = implode(' ', $args);

        $this->docker->setProfile($profile);
        $this->docker->passthru($commandLine);
    }
}
