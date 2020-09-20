<?php
class CLI
{
	private $rawArgs = [];
	private $args = [];
	private $name = null;

	public function __construct(array $argv, bool $showErrors=false)
	{
		if($showErrors){
			$this->enableErrors();
		}

		$this->parseArgs($argv);
		$this->setName($argv[0]);
	}

	public function enableErrors()
	{
		error_reporting(-1);
		ini_set("display_errors", true);
	}

	public function setName(string $name): void
	{
		$this->name = $name;
	}

	public function getScript(?bool $withPath=true): string
	{
		return $withPath ? $this->name : basename($this->name);
	}

	// FIXME: I don't like that this function is here, it's a copy of another function in the base config object
	// NOTE: it's also nothing to do with the CLI really, so it's awkward to have it here
	static public function getToolPath(string $subpath=null): string
	{
		return dirname(__DIR__) . $subpath;
	}

	public function parseArgs(array $argv): array
	{
		$args = implode('&', array_slice($argv, 1));

		$args = preg_replace_callback(
			'/(^|(?<=&))[^=[&]+/',
			function($key) { return bin2hex(urldecode($key[0])); },
			$args
		);

		parse_str($args, $this->args);

		array_map(function($k) {
			static $count = 0;

			$v = $this->args[$k];
			unset($this->args[$k]);
			$k = hex2bin($k);

			// Sometimes we need the raw unadultered arguments
			$this->rawArgs[$k] = trim(implode("=", [$k,$v]), '=');

			$k = str_replace("--","",$k);

			if(!empty($v)){
				$this->args[$k] = $v;
			}else{
				$this->args[$count++] = $k;
			}
		}, array_keys($this->args));

		return $this->args;
	}

	public function getArgList(bool $rawArgs=false): array
	{
		return $rawArgs ? $this->rawArgs : $this->args;
	}

	public function getArg(string $name, $default=null): ?string
	{
		foreach($this->args as $key => $value){
			if(is_int($key) && $value === $name) return "true";
			if($key === $name) return $value;
		}

		return $default;
	}

	public function setArg(string $name, $value=null): void
	{
		if($value === null){
			$this->args[] = $name;
		}else{
			$this->args[$name] = $value;
		}
	}

	public function getArgWithVal(string $name, $default=null): ?string
	{
		$arg = $this->getArg($name, $default);

		return $arg != "true" ? $arg : $default;
	}

	public function hasArg($name): ?bool
	{
		if(is_string($name)) $name = [$name];
		if(!is_array($name)) throw new Exception("name parameter must be string or array");

		foreach($name as $test){
			foreach($this->args as $key => $value){
				if(is_int($key) && $value === $test) return true;
				if($key === $name) return true;
			}
		}

		return false;
	}

	public function countArgs(): int
	{
		return count($this->args);
	}

	static public function ask(string $question, array $accept): string
	{
		$responses = "(Accepts: " . implode(", ", $accept) . "): ";
		$reply = readline(Text::write("{yel}$question $responses{end}"));

		return $reply;
	}
}
