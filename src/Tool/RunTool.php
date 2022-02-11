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
        $this->registerCommand('script', null, true);
    }

    public function getToolMetadata(): array
    {
        return [
            'title' => 'Script Runner',
            'short_description' => 'A tool to run scripts configured as part of projects',
            'description' => implode("\n", [
                "This tool allows projects to define scripts that will do actions, similar to 'yarn start'.",
                "However this tool allows projects to define dependencies and this allows projects to start",
                "and stop their dependencies as each project requires. Making developing with complex stacks",
                "of software easier because developers can develop orchestrated stacks of software to run on",
                "demand instead of requiring each developer to know each project and each dependency and how",
                "to start them",
            ]),
            'options' => implode("\n\t", [
                "script: Run a script",
                "--group=name: The group to select the project from",
                "--project=name: The project in that group to execute the script from",
                "--name=script: The script in that project to execute",
            ])
        ];
    }

    public function scriptCommand(string $group, string $project, string $name): void
    {
        try{
            $this->runService->reset();
            $this->runService->run($group, $project, $name);
        }catch(ConfigMissingException $exception){
            $this->cli->failure("The project directory for '$project' in group '$group' was not found");
        }
    }
}
