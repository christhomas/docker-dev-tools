<?php declare(strict_types=1);

namespace DDT\Tool;

use DDT\Config\ProjectGroupConfig;
use DDT\CLI;
use DDT\GitService;

class ProjectTool extends Tool
{
    /** @var \DDT\Config\ProjectGroupConfig  */
    private $config;

    /** @var \DDT\GitService */
    private $repoService;

    public function __construct(CLI $cli, ProjectGroupConfig $config, GitService $repoService)
    {
    	parent::__construct('project', $cli);

        $this->config = $config;
        $this->repoService = $repoService;
        $this->registerCommand('list', 'listCommand');
        $this->registerCommand('add-group', 'addGroupCommand');
        $this->registerCommand('remove-group', 'removeGroupCommand');
        $this->registerCommand('add-project', 'addProjectCommand');
        $this->registerCommand('remove-project', 'removeProjectCommand');
    }

    public function getToolMetadata(): array
    {
        return [
            'tool' => 'Project Management Tool',
            'short_description' => 'A tool to manage projects installed and control them using scripts and hooks',
            'description' => trim(
                "This tool allows projects to be installed and managed by the tooling system.\n".
                "It can run scripts, and perform actions upon the projects, using functionality\n".
                "or scripts installed within the projects themselves\n"
            ),
            'options' => trim(
                "{cyn}Managing Groups{end}:\n".
                "\tlist: List the project groups\n".
                "\tadd-group --name=group-name: Create a new project group.\n".
                "\tremove-group --name=group-name: Remove a project group.\n".
                "\n".
                "{cyn}Adding Projects{end}:\n".
                "\tadd-project --name=<project-name>: Will add a new project that already exists on the disk.\n".
                "\t--group=<group>: (REQUIRED) The group to which this project will be added\n".
                "\t--dir=<path>: (REQUIRED) The location on the filesystem for this project\n".
                "\t--type=<npm|composer|ddt>: (OPTIONAL: default=ddt) One of the supported project types. {yel}(See Project Type list below){end}\n".
                "\n".
                "{cyn}Removing Projects{end}:\n".
                "\tremove-project --name=project-name: Remove the project from the group given.\n".
                "\t--group: (REQUIRED) The group from which this project will be removed\n".
                "\t--delete: (OPTIONAL) {red}**DANGEROUS**{end} This option will not only remove the project from a group, but delete the files from disk\n".
                "\n".
                "{cyn}Project Types{end}:\n".
                "\tThese just define where the configuration will be stored, it has one of the following values:\n\n".
                "\tnpm: This project type will use the 'package.json' file.\n".
                "\tcomposer: This project type will use the 'composer.json' file.\n".
                "\tddt: {yel}(default if no type given){end} This project will use the 'ddt-project.json' file\n"
            ),
        ];
    }

    public function isProjectType(string $type=null): bool
    {
        return in_array($type, ['composer', 'npm', 'ddt']);
    }

    public function listCommand(): void
    {
        $this->cli->print("{blu}Project Group List:{end}\n");

        $groupList = $this->config->listGroup();

        if(empty($groupList)){
            $this->cli->print("There are no configured groups\n");
        }
        
        foreach($groupList as $group => $projectList) {
            $this->cli->print("\nGroup: {yel}$group{end}\n");
            $this->cli->print("Projects: \n");

            if(empty($projectList)){
                $this->cli->print("\tThere are no configured projects\n");
            }

            foreach($projectList as $name => $config) {
                $this->cli->print("{cyn}$name{end}:\n");
                $this->cli->print("\tPath: {$config['path']}\n");
                $this->cli->print("\tType: {$config['type']}\n");
                $this->cli->print("\tRepository:\n");
                $this->cli->print("\t\tUrl: {$config['repo']['url']}\n");
                $this->cli->print("\t\tRemote: {$config['repo']['remote']}\n");
            }
        }
    }

    public function addGroupCommand(string $name): void
    {
        $this->cli->print("{blu}Adding group '$name'{end}\n");

        if($this->config->addGroup($name)){
            $this->cli->print("{grn}Project was added, listing projects{end}...\n");

            $this->listCommand();
        }else{
            $this->cli->print("{red}Adding the group '$name' has failed (maybe it already exists?){end}\n");
        }
    }

    public function removeGroupCommand(string $name): void
    {
        $this->cli->print("{blu}Removing group '$name'{end}\n");

        if($this->config->removeGroup($name)){
            $this->cli->print("{grn}Project was removed, listing projects{end}...\n");
            $this->listCommand();
        }else{
            $this->cli->print("{red}Removing the group '$name' has failed (maybe it doesn't exist?){end}\n");
        }
    }

    public function addProjectCommand(string $group, string $dir, ?string $name=null, ?string $type='ddt', ?string $git=null, ?string $remote='origin'): void
    {
        $this->cli->print("{blu}Adding project{end}\n");

        if($this->isProjectType($type) === false){
            $this->cli->print("{red}The type '$type' is not a recognised value, see help for options{end}\n");
            return;
        }

        if($name === null){
            $name = basename($dir);
        }

        if(is_dir($dir)){
            $this->cli->print("Project '$name' exists?: {grn}Yes{end}\n");

            try{
                $remoteUrl = $this->repoService->getRemote($dir, $remote);
                $this->cli->print("Git Remote Url ($remote): {grn}$remoteUrl{end}\n");    
            }catch(\Exception $e){
                $this->cli->print("Git Remote Url ($remote): {red}Error occurred, is this a git directory? Will skip this...{end}\n");
                // Prevent the git configuration from being saved
                $remoteUrl = null;
            }
            
            if($this->config->addProject($group, $name, $dir, $type, $remoteUrl, $remote)){
                $this->cli->success("The project '$name' was successfully added to the group '$group'\n");
            }else{
                $this->cli->failure("The project '$name' failed to be added to the group '$group'\n");
            }
        }else if($git !== null){
            $this->cli->print("Project '$name' exists?: {red}No{end}\n");
        }else{
            $this->cli->print("{red}Project '$name' in directory '$dir' does not exist, but no --git option given in order to clone it{end}\n");
        }
    }

    public function removeProjectCommand(string $group, string $name, ?bool $delete=false): void
    {
        $this->cli->print("{blu}Removing Project{end}\n");

        if($this->config->removeProject($group, $name)){
            $this->cli->success("The project '$name' was successfully removed from the group '$group'\n");
        }else{
            $this->cli->failure("The project '$name' failed to be removed from the group '$group'\n");
        }
    }
}
