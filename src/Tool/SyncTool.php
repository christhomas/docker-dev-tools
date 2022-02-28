<?php declare(strict_types=1);

namespace DDT\Tool;

use DDT\CLI;
use DDT\Config\SystemConfig;
use DDT\Text\Text;

class SyncTool extends Tool
{
    /** @var Text */
    private $text;

    /** @var SystemConfig  */
    private $config;

    public function __construct(CLI $cli, Text $text, SystemConfig $config)
    {
    	parent::__construct('sync', $cli);

        $this->text = $text;
        $this->config = $config;
    }

    public function getToolMetadata(): array
    {
        $entrypoint = $this->cli->getScript(false) . " " . $this->getToolName();

        return [
            'title' => 'Container File Sync Tool',
            'short_description' => 'A tool to sync your local project with a docker container',
            'description' => [
                "This tool will watch and sync changes from your local file system into a docker container.",
                "{yel}It does not handle file changes inside the container and syncing them back to your local setup{end}",
                "This tool only syncs in one direction from your local system to the docker container.",
            ],
            'examples' => [
                "{cyn}Managing Sync Profiles{end}",
                "\t$entrypoint  --docker=company-staging --add-profile=phpfpm --local-dir=/a/directory/path --remote-dir=/www",
                "\t$entrypoint  --docker=company-staging --remove-profile=phpfpm",
                "{cyn}Watching Changes{end}",
                "\t$entrypoint --docker=company-staging --profile=phpfpm --watch",
                "\t$entrypoint --docker=company-staging --profile=phpfpm --write=filename.txt",
            ],
            'options' => [
                "--docker: Which docker configuration to use",
                "--add-profile: The name of the profile to create",
                "--remove-profile: The name of the profile to remove",
                "--list-profile: List all the sync profiles for this specified docker configuration",
                "--container: (Optional: defaults to profile name) The name of the container to connect to",
                "--local-dir: The local directory to watch for modifications",
                "--remote-dir: The directory inside the container to sync the changed files into",
                "--profile: The name of the profile to use",
                "--watch: To create a new 'fswatch' on the selected profile",
                "--write=filename.txt: Which file was modified and should be uploaded\n",
                "{cyn}Ignore Rules{end}: Ignore Rules are global and apply to all projects",
                "--add-ignore-rule=^.git",
                "--remove-ignore-rule=^.git",    
            ],
            'notes' => [
                "The parameter {yel}--add-profile{end} depends on: {yel}local-dir, remote-dir{end}",
                "options in order to create the profile.\n",
                "{yel}Please remember, any changes inside the container are not respected here,",
                "everything is overwritten{end}",
            ],
        ];
    }
}

/*
try{
    $config = \DDT\Config\SystemConfig::instance()
    $docker = new Docker($config);
    $watcher = new Watcher($cli->getScript(false), $config, $docker);
}catch(DockerNotRunningException $e){
    $this->cli->failure($e->getMessage());
}catch(DockerMissingException $e){
    $this->cli->failure($e->getMessage());
}catch(Exception $e){
    if(!$this->cli->isCommand('fswatch')){
        $answer = $this->cli->ask("fswatch is not installed, install it?", ['yes', 'no']);
        if($answer === 'yes'){
            $os = strtolower(PHP_OS);
            if($os === 'darwin') $this->cli->passthru('brew install fswatch');
            if($os === 'linux') $this->cli->passthru('api-get install fswatch');
        }
    }

    if(!$this->cli->isCommand('fswatch')){
        $this->cli->print($this->text->box($e->getMessage(), 'white', 'red'));
        exit(1);
    }
}

function help(CLI $cli)
{
	$script = $cli->getScript(false);

	$this->cli->print(<<<EOF
    {yel}Usage Examples: {end}

    {blu}Description:{end}

    {blu}Options:{end}     
    {blu}Notes:{end}
        


EOF
    );

	exit(0);
}

if($cli->hasArg('help') || $cli->countArgs() === 0){
    help($cli);
}

if($cli->hasArg(['list-ignore-rule','list-ignore-rules'])){
    $ignoreRuleList = $watcher->listIgnoreRules();

    $this->cli->print("{blu}Ignore Rules{end}:\n");
    foreach($ignoreRuleList as $ignoreRule){
        $this->cli->print("Rule: '{yel}$ignoreRule{end}'\n");
    }
    if(empty($ignoreRuleList)){
        $this->cli->print("There are no ignore rules in place\n");
    }

    exit(0);
}

if($ignoreRule = $cli->getArgWithVal('add-ignore-rule')){
    $watcher->addIgnoreRule($ignoreRule);
    exit(0);
}

if($ignoreRule = $cli->getArgWithVal('remove-ignore-rule')){
    $watcher->removeIgnoreRule($ignoreRule);
    exit(0);
}

///////////////////////////////////////////////////////////////
// EVERYTHING BELOW HERE REQUIRES A VALID DOCKER PROFILE
///////////////////////////////////////////////////////////////

$name = $cli->getArgWithVal('docker');
if($name !== null){
	$dockerProfile = $docker->getProfile($name);

	if($dockerProfile === null){
		$this->cli->failure("Docker profile '$name' did not exist");
	}

	$docker->setProfile($dockerProfile);
}else{
    $this->cli->failure("No valid docker profile given");
}

if($cli->hasArg(['list-profile', 'list-profiles'])){
    try{
		$profileList = $watcher->listProfiles($dockerProfile);
    }catch(Exception $e){
        $this->cli->failure($e->getMessage());
    }

    $this->cli->print("{blu}Profile List{end}:\n");
    foreach($profileList as $name => $profile){
        $this->cli->print("{cyn}$name{end}: to container '{yel}{$profile->getContainer()}{end}' with local dir '{yel}{$profile->getLocalDir()}{end}' and remote dir '{yel}{$profile->getRemoteDir()}{end}'\n");
    }
    if(empty($profileList)){
        $this->cli->print("There were no profiles with this docker configuration\n");
    }

    exit(0);
}

if(($syncProfile = $cli->getArgWithVal('add-profile')) !== null){
    $container = $cli->getArgWithVal('container', $syncProfile);
    $localDir = $cli->getArgWithVal('local-dir');
    $remoteDir = $cli->getArgWithVal('remote-dir');

    if($container === null) $this->cli->failure("--container parameter was not valid");
    if($localDir === null) $this->cli->failure("--local-dir parameter was not valid");
    if($remoteDir === null) $this->cli->failure("--remote-dir parameter was not valid");

    if($watcher->addProfile($dockerProfile, $syncProfile, $container, $localDir, $remoteDir)){
		$this->cli->success("Docker Sync Profile '$syncProfile' using docker '{$dockerProfile->getName()}' and target container '$container' between '$localDir' to '$remoteDir' was written successfully");
	}else{
		$this->cli->failure("Docker Sync Profile '$syncProfile' using docker '{$dockerProfile->getName()}' did not write successfully");
    }
}

if(($syncProfile = $cli->getArgWithVal('remove-profile')) !== null){
    if($watcher->removeProfile($dockerProfile, $syncProfile)){
		$this->cli->success("Docker Sync Profile '$syncProfile' using docker '{$dockerProfile->getName()}' was removed successfully");
	}else{
		$this->cli->failure("Docker Sync Profile '$syncProfile' using docker '{$dockerProfile->getName()}' did not remove successfully");
    }
}

///////////////////////////////////////////////////////////////
// EVERYTHING BELOW HERE REQUIRES A VALID SYNC PROFILE
///////////////////////////////////////////////////////////////

$name = $cli->getArgWithVal('profile');
if($name !== null){
	$syncProfile = $watcher->getProfile($dockerProfile, $name);

	if($syncProfile === null){
		$this->cli->failure("Docker profile '$name' did not exist");
	}
}else{
	$this->cli->failure("No valid sync profile given");
}

if($cli->hasArg('watch')){
    try{
        $this->cli->print("{blu}Starting watcher process using docker '{$dockerProfile->getName()}' and container '{$syncProfile->getContainer()}'...{end}\n");
        if($watcher->watch($dockerProfile, $syncProfile)){
            $this->cli->success("Terminated successfully");
        }else{
            $this->cli->failure("Error: fswatch failed with an unknown error");
        }
    }catch(Exception $e){
        $this->cli->failure("The watcher process has exited abnormally");
    }
}

if(($localFilename = $cli->getArgWithVal('write')) !== null){
    try{
        $relativeFilename = str_replace($syncProfile->getLocalDir(), "", $localFilename);
		$remoteFilename = $syncProfile->getRemoteFilename($localFilename);
		$now = (new DateTime())->format("Y-m-d H:i:s");
		$this->cli->print("$now - $relativeFilename => $remoteFilename ");

		if(is_dir($localFilename)){
			$this->cli->success("{yel}IGNORED (WAS DIRECTORY){end}");
        }else if(!file_exists($localFilename)){
			$this->cli->success("{yel}IGNORED (FILE NOT FOUND){end}");
		}else if($watcher->shouldIgnore($syncProfile, $localFilename)){
			$this->cli->success("{yel}IGNORED (DUE TO RULES){end}");
        }else if($watcher->write($syncProfile, $localFilename)){
			$this->cli->success("SUCCESS");
        }else{
			$this->cli->failure("FAILURE");
        }
    }catch(Exception $e){
        $this->cli->failure("EXCEPTION: ".$e->getMessage());
    }
}

$this->cli->failure('no action taken');
*/