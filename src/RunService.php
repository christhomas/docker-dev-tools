<?php
namespace DDT;

use DDT\CLI;
use DDT\Config\External\StandardProjectConfig;

class RunService
{
	/** @var CLI */
	private $cli;

	/** @var array The stack of scripts running and used to detect circular dependencies */
	private $stack;

	public function __construct(CLI $cli)
	{
		$this->cli = $cli;
	}

	public function reset(): void
	{
		$this->stack = [];
	}

	public function run(StandardProjectConfig $projectConfig, string $script)
	{
		$path = $projectConfig->getPath();
		$script = $projectConfig->getKey(".scripts.$script");

		$this->cli->passthru("cd $path; $script");
	}
}
