<?php declare(strict_types=1);

namespace DDT\Tool;

use DDT\Config\ProjectGroupConfig;
use DDT\CLI;
use DDT\Exceptions\Config\ConfigMissingException;
use DDT\RunService;

class RunTool extends Tool
{
    /** @var \DDT\Config\ProjectGroupConfig  */
    private $config;

    /** @var \DDT\RunService */
    private $runService;

    public function __construct(CLI $cli, ProjectGroupConfig $config, RunService $runService)
    {
    	parent::__construct('run', $cli);

        $this->config = $config;
        $this->runService = $runService;
    }

    public function getTitle(): string
    {
        return 'Script Runner';
    }

    public function getShortDescription(): string
    {
        return 'A tool to run scripts configured as part of projects';
    }

    public function getDescription(): string
    {
		return "\t" . implode("\n\t", [
            "This tool allows projects to define scripts that will do actions, similar to 'yarn start'.",
            "However this tool allows projects to define dependencies and this allows projects to start",
            "and stop their dependencies as each project requires. Making developing with complex stacks",
            "of software easier because developers can develop orchestrated stacks of software to run on",
            "demand instead of requiring each developer to know each project and each dependency and how",
            "to start them",
        ]);
    }

    public function getExamples(): string
    {
        /*$entrypoint = $this->cli->getScript(false) . " " . $this->getName();

        return implode("\n", [
            "{yel}Usage Example:{end} $entrypoint {yel}install name url=https://github.com/something/extension_repo.git{end}",
            "{yel}Usage Example:{end} $entrypoint {yel}uninstall plista{end}"
        ]);*/
        return "";
    }

    public function getOptions(): string
	{
		return "\t" . implode("\n\t", [
            "{cyn}Managing Groups{end}:",
            "--list: List the project groups",
            "--add-group=group-name: Create a new project group.",
            "--remove-group=company-name: Remove a project group.",

            "\n\t{cyn}Adding Projects{end}:",
            "--add-project=project-name: Will add a new project that already exists on the disk.",
            "--group: (REQUIRED) The group to which this project will be added",
            "--path: (REQUIRED) The location on the filesystem for this project",
            "--type: (OPTIONAL: default=ddt) One of the supported project types. {yel}(See Project Type list below){end}",
		]);
	}

    public function scriptCommand(string $group, string $project, string $name): void
    {
        try{
            $projectConfig = $this->config->getProjectConfig($group, $project);

            $this->runService->reset();
            $this->runService->run($projectConfig, $name);
        }catch(ConfigMissingException $exception){
            $this->cli->failure("The project directory for '$project' in group '$group' was not found");
        }
    }
}
