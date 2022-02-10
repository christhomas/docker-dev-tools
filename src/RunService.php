<?php
namespace DDT;

use DDT\CLI;
use DDT\Config\External\StandardProjectConfig;
use DDT\Config\ProjectGroupConfig;

class RunService
{
	/** @var CLI */
	private $cli;

	/** @var ProjectGroupConfig */
	private $projectGroupConfig;

	/** @var array The stack of scripts running and used to detect circular dependencies */
	private $stack;

	public function __construct(CLI $cli, ProjectGroupConfig $config)
	{
		$this->cli = $cli;
		$this->projectGroupConfig = $config;
	}

	public function reset(): void
	{
		$this->stack = [];
	}

	private function makeKey(StandardProjectConfig $projectConfig, string $script): string
	{
		return $projectConfig->getPath() . "@" . $script;
	}

	private function isRunning(StandardProjectConfig $projectConfig, string $script): bool
	{
		// does this project have a script named this?
		// is this project already running this script? (prevent infinite loops)
		$key = $this->makeKey($projectConfig, $script);

		return in_array($key, $this->stack);
	}

	private function push(StandardProjectConfig $projectConfig, string $script): bool
	{
		// if not, add it to the stack and return true;
		$key = $this->makeKey($projectConfig, $script);
		// TODO: do I need to keep track of any runtime data here?
		$this->stack[] = $key;
		$this->cli->debug("{red}[RUNSERVICE]:{end}\n{cyn}Stack(push = $key):\n".implode("\n", $this->stack)."{end}\n");
		// I don't know how to handle failure yet
		return true;
	}

	public function getProject(string $group, string $project): StandardProjectConfig
	{
		//	TODO: how to handle when a project is not found, it'll throw exceptions?
		return $this->projectGroupConfig->getProjectConfig($group, $project);
	}

	public function run(string $group, string $project, string $script)
	{
		try{
			$this->cli->debug("{red}[RUNSERVICE]:{end} Running: $group, $project, $script\n");
			$projectConfig = $this->getProject($group, $project);
		
			if($this->isRunning($projectConfig, $script) === false){
				$this->push($projectConfig, $script);

				if($this->runDependencies($projectConfig, $group, $script) === true){
					$path = $projectConfig->getPath();
					$command = $projectConfig->getKey(".scripts.$script");
		
					$this->cli->print("{blu}Run Script:{end} group[{yel}$group{end}], project[{yel}$project{end}], script[{yel}$script{end}]\n");
					// TODO: how to handle when a script fails?
					$this->cli->passthru("cd $path; $command");
				}
			}else{
				// show an error about non-entrant scripts
				$key = $this->makeKey($projectConfig, $script);
				$this->cli->debug("{red}[RUNSERVICE]:{end} Script already running: $key\n");
			}
		}catch(\Exception $e){
			$this->cli->debug("{red}[RUNSERVICE]:{end} {red}Exception@run:{end} ".get_class($e)." => {$e->getMessage()}\n");
			return false;
		}
	}

	public function runDependencies(StandardProjectConfig $projectConfig, string $group, string $script): bool
	{
		$dependencies = $projectConfig->getDependencies($script);

		foreach($dependencies as $project => $d){
			$group = array_key_exists('group', $d) ? $d['group'] : $group;

			$t = array_map(function($k, $v) { return $k===$v ? $k : "$k=$v"; }, array_keys($d['scripts']), array_values($d['scripts']));
			$this->cli->debug("{red}[RUNSERVICE]{end}: Dependencies($project@$group): [".implode(",", $t)."]\n");

			if(array_key_exists('scripts', $d)){
				if(array_key_exists($script, $d['scripts'])){
					$script = $d['scripts'][$script];
				}
			}

			// TODO: how to handle a the return value from this?
			$this->run($group, $project, $script);
		}
		
		return true;
	}
}
