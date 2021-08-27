<?php declare(strict_types=1);

namespace DDT;
class CLI
{
	private $args = [];
	private $script = null;

	public function __construct(array $argv)
	{
		$this->setScript($argv[0]);
		$this->setArgs(array_slice($argv, 1));
	}

	public function enableErrors(bool $showErrors=false)
	{
		if($showErrors){
			error_reporting(-1);
			ini_set('display_errors', 'true');
		}else{
			error_reporting(0);
			ini_set('display_errors', 'false');
		}
	}

	public function setScript(string $script): void
	{
		$this->script = $script;
	}

	public function getScript(?bool $withPath=true): string
	{
		return $withPath ? $this->script : basename($this->script);
	}

	static public function ask(string $question, array $accept): string
	{
		$responses = "(Accepts: " . implode(", ", $accept) . "): ";
		$reply = readline(\Text::write("{yel}$question $responses{end}"));

		return $reply;
	}

	public function setArgs(array $argv): array
	{
		$this->args = [];

		foreach($argv as $v){
			$v = explode('=', $v);

			$this->args[] = ['name' => array_shift($v), 'value' => array_shift($v)];
		}

		return $this->args;
	}

	public function shiftArg(): ?array
	{
		return array_shift($this->args);
	}

	public function countArgs(): int
	{
		return count($this->args);
	}

	public function getArgList(): array
	{
		return $this->args;
	}

	public function getArg(string $name, $default=null): ?string
	{
		foreach($this->args as $arg){
			if($arg['name'] === $name){
				return empty($arg['value']) ? "true" : $arg['value'];
			}
		}

		return $default;
	}

	public function hasArg($name): ?bool
	{
		if(is_string($name)) $name = [$name];
		if(!is_array($name)) throw new \Exception("name parameter must be string or array");

		foreach($name as $test){
			if($this->getArg($test) === null){
				return false;
			}
		}

		return true;
	}

	public function isCommand(string $command): bool
	{
		return \Shell::isCommand($command);
	}

	public function sudo(): CLI
	{
		\Shell::sudo();

		return $this;
	}

	public function getStdErr(): string
	{
		return \Shell::$stderr;
	}

	public function exec(string $command)
	{
		return \Shell::exec($command);
	}

	public function passthru(string $command, bool $throw=true): int
	{
		return \Shell::passthru($command, $throw);
	}

	public function print($string)
	{
		\Text::print($string);
	}

	public function failure(?string $string=null)
	{
		\Script::failure($string);
	}

	// DEPRECATED FUNCTIONALITY BELOW, TRY TO NOT USE ANY OF THE FOLLOWING METHODS

	/**
	 *	FIXME: I don't like that this function is here, it's a copy of another function in the base config object
	 * 	NOTE: it's also nothing to do with the CLI really, so it's awkward to have it here
	 * @param string|null $subpath
	 * @return string
	 * @deprecated
	 */
	static public function getToolPath(string $subpath=null): string
	{
		return dirname(__DIR__) . $subpath;
	}

	/**
	 * @deprecated
	 */
	public function setArg(string $name, $value=null): void
	{
		$this->args[] = ['name' => $name, 'value' => $value];
	}

	/**
	 * @deprecated
	 */
	public function getArgWithVal(string $name, $default=null): ?string
	{
		$arg = $this->getArg($name, $default);

		return $arg != "true" ? $arg : $default;
	}

	/**
	 * @deprecated
	 */
	public function getArgByIndex(int $index): ?string
	{
		return array_key_exists($index, $this->args) ? $this->args[$index]['value'] : null;
	}
}
