<?php declare(strict_types=1);

namespace DDT\Tool;

use DDT\CLI;
use DDT\Config\ProjectGroupConfig;
use DDT\Exceptions\Config\ConfigMissingException;
use DDT\Services\RunService;
use DDT\Text\Table;

class RunTool extends Tool
{
    public function __construct(CLI $cli)
    {
    	parent::__construct('run', $cli);

        $this->setToolCommand('script', null, true);
        $this->setToolCommand('--list', 'list');
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
            'examples' => [
                "{yel}{$this->getEntrypoint()} run{end}: This help",
                "{yel}{$this->getEntrypoint()} run --name=start --group=mycompany --project=backendapi{end}: Run the 'start' script from the 'backendapi' project in the 'mycompany' group",
                "{yel}{$this->getEntrypoint()} run <script> <group> <project>{end}: The same command as above, but using anonymous parameters",
                "{yel}{$this->getEntrypoint()} run --list{end}: Will output all the possible scripts that it's possible to run",
            ],
        ];
    }

    public function list(ProjectGroupConfig $config): void
    {
        /* @var Table $table */
        $table = container(Table::class);
        $table->addRow(["{yel}Group{end}", "{yel}Project{end}", "{yel}Script Name{end}", "{yel}Script Command{end}"]);

        foreach($config->listGroup() as $group => $groupList){
            foreach($groupList as $project => $projectList){
                $projectConfig = $config->getProjectConfig($group, $project);
                foreach($projectConfig->listScripts() as $script => $scriptCommand){
                    $table->addRow([$group, $project, $script, $scriptCommand]);
                }
            }
        }
        
        $this->cli->print($table->render());
    }

    public function script(RunService $runService, string $name, string $group, string $project): void
    {
        try{
            $runService->reset();
            $runService->run($group, $project, $name);
        }catch(ConfigMissingException $exception){
            $this->cli->failure("The project directory for '$project' in group '$group' was not found");
        }
    }
}
