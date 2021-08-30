<?php declare(strict_types=1);

namespace DDT\Tool;

use DDT\CLI;
use DDT\Config\SystemConfig;

class ProjectTool extends Tool
{
    /** @var \DDT\Config\SystemConfig  */
    private $config;

    public function __construct(CLI $cli, SystemConfig $config)
    {
    	parent::__construct('extension', $cli);

        $this->config = $config;
    }

    public function getTitle(): string
    {
        return 'Extension Management Tool';
    }

    public function getShortDescription(): string
    {
        return 'A tool to manage tool extensions and update them';
    }

    public function getDescription(): string
    {
		return "This tool will manage extensions installed within the tools. It can install, uninstall, or update them. At this time
        the tool only supports extensions from GIT repositories";
    }

    public function getExamples(): string
    {
        $entrypoint = $this->cli->getScript(false) . " " . $this->getName();

        return implode("\n", [
            "{yel}Usage Example:{end} $entrypoint {yel}install name url=https://github.com/something/extension_repo.git{end}",
            "{yel}Usage Example:{end} $entrypoint {yel}uninstall plista{end}"
        ]);   
    }

    public function getOptions(): string
	{
		$alias = $this->config->getKey('.ip_address') ?? 'unknown';

		return "\t" . implode("\n\t", [
            "--install=XXX: Will install a new extension using the parameters value as the given name",
            "--url=XXX: Will use this location to install the extension, only git repositories are supported",
            "--uninstall=XXX: Will uninstall an extension with the given name",
            "--list: Will list the installed extensions",
            "--update: Will update all extensions from their repository urls given during installation",
		]);
	}
}

/*
$this->cli->title("DDT PROJECT", "Quickly manage your projects, push or pull them with various filters, etc");

$config = container(\DDT\Config\SystemConfig::class);

function help(CLI $cli)
{
    $script = $cli->getScript(false);

    Text::print(<<<EOF
    {yel}Usage Examples: {end}
        $script --pull
        $script --push=api-server
        $script --push=api-server --pull=tools
        $script --add=api-server --git=https://gitlab.example.com/projects/the-api-server.git
        $script --remove=api-server --delete
        $script --add-hook=onsuccess --script="{project_dir}/setup.sh"
        $script --remove-hook=after_pull --all

    {blu}Description:{end}
        This tool will allow you to clone projects into your work 
        This tool will quickly cycle through all the projects installed into the configured project root and pull them. 

    {blu}Options:{end}
        {cyn}General{end}
        --help: This help information

        {cyn}Managing projects{end}
        --add=XXX: This will add a project cloned into a folder with this name
        --git=XXX: In combination with --add, will clone the git repository to a directory with the name in the --add parameter
        --branch=XXX: In combination with --add, will clone a specific branch from the repository
        --dir=XXX: In combination with --add, will override the directory to clone the git repository into
        --remove=XXX: This will remove the following project from your configuration (but will not delete any files)
        --delete: In combination with --remove, will delete the project from your system too (but will not delete any dependencies it installed when you cloned it)
        --import=XXX: This will add the existing git cloned project to the configuration using it's current configuration
        --import-scan: This will loop through all the projects in the projects directory and add anything that's missing

        {cyn}Synchronising projects{end}
        --pull=XXX (optional): This will pull all projects (optionally with a filter if provided) from their repositories to your local system, ignoring those which have had changes
        --push=XXX (optional): This will push all projects (optionally with a filter if provided) to their repositories from your local system
        --show-branch=XXX (optional): This will only show the branches for each project, the optional XXX part will show only the filtered project

        {cyn}Managing Hooks{end}
        --list-hooks: This will list all the configured hooks with their respective scripts
        --add-hook=XXX: This will add a script to the named hook in this parameter
        --remove-hook=XXX: This will remove a script from the named hook in this parameter
        --script=XXX: In combination with --add-hook this will add the following script to the given hook
        --all: WARNING! In combination with --remove-hook this will remove all the hooks of the given type
     
    {blu}Notes:{end}
        During --push or --pull, projects that have local changes will not be synchronised and display as skipped. No changes will be made because it's unsafe to 
        assume any operation can work in all scenarios.
        
        Hooks are unique, therefore a script can only appear once and duplicate hooks will not be added.
        There is no way to reorder hooks, they must be added one by one in the sequence desired.
        Supported Hooks:
            - {yel}after_pull{end}: Will be executed after the project git pull was completed
            - {yel}after_push{end}: Will be executed after the project git push was completed
        Supported Template parameters:
            - {yel}file{end}: Will use the enclosed string as a filename and test it's existence. It will remove the entire command if it failed the test
                - {cyn}Example{end}: "sh {file}{project_dir}/setup.sh{file}" will test if file "{project_dir}/setup.sh" exists 
            - {yel}project_dir{end}: Will be replaced by the directory for the project
                - {cyn}Example{end}: "sh {project_dir}/setup.sh" will become "sh /path/to/project/folder/setup.sh"


EOF
    );

    exit(0);
}

if($cli->hasArg('help') || $cli->countArgs() === 0){
    help($cli);
}

//*************************************************************
// LIST HOOKS
//*************************************************************
if($cli->getArg("list-hook") || $cli->getArg("list-hooks")){
	$repoSync = new RepositorySync($config);
    $list = $repoSync->listHookNames();
    foreach($list as $name){
        Text::print("{blu}Hook{end}: {yel}$name{end}\n");
        $scripts = $repoSync->listHook($name);
        if(!empty($scripts)) {
            Text::print(" - " . implode("\n - ", $scripts) . "\n\n");
        }else{
            Text::print(" --- NO SCRIPTS --- \n\n");
        }
    }

    exit(0);
}

//*************************************************************
// ADD A NEW HOOK
//*************************************************************
if(($name = $cli->getArgWithVal("add-hook")) !== null){
    $script = $cli->getArgWithVal("script");

    if(empty($script)){
        $this->cli->failure("You can't add a hook without a script, it makes no sense\n");
    }

	$repoSync = new RepositorySync($config);
    if($repoSync->addHook($name, $script)){
        $this->cli->success("Project Sync Hook '$name' using script '$script' was added successfully");
    }else{
        $this->cli->failure("Project Sync Hook '$name' has failed to add script '$script'");
    }
}

//*************************************************************
// REMOVE A HOOK
//*************************************************************
if(($name = $cli->getArgWithVal("remove-hook")) !== null){
	$repoSync = new RepositorySync($config);
    $list = $repoSync->listHook($name);

    $removed = false;
    $script = $cli->getArgWithVal("script");
    if($script){
        foreach($list as $key => $value){
            if($script === $value){
                if($repoSync->removeHook($name, $key)) $removed = true;
            }
        }
    }

    $all = $cli->getArg("all");
    if($all){
        foreach($list as $key => $value){
            if($repoSync->removeHook($name, $key)) $removed = true;
        }
    }

    if($removed){
        $this->cli->success("Project Sync Hook '$name' has successfully removed the script(s)");
    }else{
        $this->cli->failure("Project Sync Hook '$name' has failed to remove the script(s)");
    }
}

//*************************************************************
// ADD A NEW PROJECT
//*************************************************************
$add = $cli->getArgWithVal('add');
$git = $cli->getArgWithVal('git');
$branch = $cli->getArgWithVal('branch');
$dir = $cli->getArgWithVal('dir');
if($add !== null){
    $projectManager = new ProjectManager($config, $add);

    try{
		$projectManager->add($git, $branch, $dir);
        $this->cli->success("Project '$add' from repository '$git' using branch '$branch' into directory '$dir' was successfully cloned");
    }catch(DirectoryExistsException $e){
        $this->cli->failure($e->getMessage());
    }catch(Exception $e){
        var_dump($e);
        $this->cli->failure("Project '$add' has failed to clone for unspecified reasons");
    }
}

//*************************************************************
// REMOVE AN EXISTING PROJECT
//*************************************************************
$remove = $cli->getArgWithVal('remove');
$delete = $cli->getArg('delete');
if($remove !== null){
    $project = new ProjectManager($config, $remove);

    try{
		$project->remove($delete);
        $this->cli->success("Project '$remove' has successfully deleted");
    }catch(Exception $e){
		$this->cli->failure("Project '$remove' has failed to delete for unspecified reasons");
    }
}

//*************************************************************
// IMPORT AN EXISTING PROJECT
//*************************************************************
$import = $cli->getArgWithVal('import');
if($import !== null){
    $projectManager = new ProjectManager($config, $import);

    try{
        $projectManager->import();
        $this->cli->success("Project '$import' has been imported successfully");
    }catch(Exception $e){
        $this->cli->failure("Project could not be imported for unspecified reasons");
    }
}

//*************************************************************
// SCAN PROJECTS FOLDER AND IMPORT ALL PROJECTS
//*************************************************************
if($cli->hasArg('import-scan')){
    $list = ProjectManager::list();

    foreach($list as $name => $project){
        $projectManager = new ProjectManager($config, $name);
		try{
			$projectManager->import();
			print(Text::write("{blu}Project{end} '{yel}$name{end}' has been imported successfully\n"));
		}catch(Exception $e){
			print(Text::red("Project '$name' has failed to import successfully\n"));
		}
    }

    $this->cli->success("Finished");
}

//*************************************************************
// LIST ALL PROJECTS WITH THEIR BRANCH NAMES
//*************************************************************
$showBranch = $cli->getArg("show-branch");
if($showBranch !== null){
	$repoSync = new RepositorySync($config);
    $projects = $repoSync->listBranches($showBranch);
    Format::projectBranchList($projects, false);

    $this->cli->success("Finished");
}

//*************************************************************
// PULL CHANGES WITH OPTIONAL FILTER PARAM
//*************************************************************
// Always do pull before push
$pullFilter = $cli->getArg("pull");
if($pullFilter !== null){
	$repoSync = new RepositorySync($config);
	$repoSync->pull($pullFilter);
}

//*************************************************************
// PUSH CHANGES WITH OPTIONAL FILTER PARAM
//*************************************************************
// Doing a push before a pull will most likely result in an error
$pushFilter = $cli->getArg("push");
if($pushFilter !== null){
	$repoSync = new RepositorySync($config);
    $repoSync->push($pushFilter);
}

die("DEAD");

$action     = $cli->getArg("push") ? "push" : "pull";
$showBranch = $cli->getArgWithVal("show-branch");
$filter     = $cli->getArgWithVal("filter") ?: $showBranch;
$showBranch = $showBranch !== null;

foreach(glob(CLI::getToolPath("/../** <<-delete this space>/.git"), GLOB_ONLYDIR) as $dir){
    $dir = dirname(realpath($dir));
    $project = basename($dir);

    if(!empty($filter) && strpos($project, $filter) === false){
        continue;
    }

    try{
		$repo = new Git();
		$status = $repo->status($dir);
		$branch = $repo->branch($dir);
		$changes = empty($status) ? "no" : "yes";

        if($showBranch){
            Text::print("{blu}Project:{end} {yel}$project ($branch){end}. Has Changes: {yel}$changes{end}\n");
        }else if($changes === "no"){
            Text::print("{blu}".ucwords($action)."ing the project:{end} {yel}$project ($branch){end} ");

			switch($action){
				case "pull":
					$repo->pull($dir, true);
					break;

				case "push":
					$repo->push($dir, true);
					break;
			}

			$repo->fetch($dir, true);

            Text::print("{grn}Done{end}\n");

            if($action === "pull"){
                $afterPull = $repoSync->parseHook("after_pull", ["project_dir" => $dir]);
                foreach($afterPull as $script){
                    Shell::passthru($script);
                }
            }
        }else{
            Text::print("{red}Skipping the project:{end} {yel}$project ($branch){end} because it has changes\n");
            Text::print("Changes:\n$status\n");
        }
    }catch(Exception $e){
        if(strpos($e->getMessage(), "no tracking information") !== false){
            Text::print("{red}No tracking branch configured{end}\n");
        }else if(strpos($e->getMessage(), "Could not read from remote repository") !== false){
            Text::print("{red}Failed{end}. There was a connectivity issue\n");
        }else{
            Text::print("{red}Failed{end}. The command failed and threw an exception\n");
        }

        Text::print("Error Output: " . $e->getMessage());
    }
}
*/