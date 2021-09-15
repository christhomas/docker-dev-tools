<?php declare(strict_types=1);

namespace DDT;

use DDT\Network\Address;
use DDT\Text\Text;

class CLI
{
	private $text;
	private $args = [];
	private $script = null;
	private $channels = [];
	private $isRoot = false;

	public static $exitCode = 0;
	public static $stdout = "";
	public static $stderr = "";

	public function __construct(array $argv, Text $text)
	{
		$this->text = $text;
		$this->setScript($argv[0]);
		$this->setArgs(array_slice($argv, 1));
		$this->listenChannel('stdout');
		$this->listenChannel('quiet');
		$this->isRoot = $this->exec('whoami', true) === 'root';

		if($this->isRoot){
			$this->print("{yel}[SYSTEM]:{end} Root user detected\n");
		}
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

	public function ask(string $question, array $accept): string
	{
		$responses = "(Accepts: " . implode(", ", $accept) . "): ";
		$reply = readline($this->text->write("{yel}$question $responses{end}"));

		return $reply;
	}

	public function listenChannel(string $channel, ?bool $state=true, ?callable $enabled=null, ?callable $disabled=null)
	{
		if($enabled === null){
			$enabled = function($text){
				$text = $this->text->write($text);
				print($text);
				return $text;
			};
		}

		if($disabled === null){
			$disabled = function($text){
				return $text;
			};
		}
		
		$this->channels[$channel] = [
			'state' => $state,
			'enabled' => $enabled,
			'disabled' => $disabled,
		];
	}

	public function toggleChannel(string $channel, bool $state)
	{
		if(array_key_exists($channel, $this->channels)){
			$this->channels[$channel]['state'] = $state;
		}
	}

	public function writeChannel(string $channel, string $text)
	{
		if(array_key_exists($channel, $this->channels)){
			if($this->channels[$channel]['state'] === true){
				return $this->channels[$channel]['enabled']($text);
			}else{
				return $this->channels[$channel]['disabled']($text);
			}
		}
	}

	public function statusChannel(string $channel): bool
	{
		if(array_key_exists($channel, $this->channels)){
			return $this->channels[$channel]['state'];
		}

		return false;
	}

	public function silenceChannel(string $channel, callable $callback): void
	{
		$this->toggleChannel($channel, false);
		$callback();
		$this->toggleChannel($channel, true);
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

	public function removeArg(string $name): ?array
	{
		foreach($this->args as $k => $v){
			if($name === $v['name']){
				unset($this->args[$k]);
				return $v;
			}
		}

		return null;
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
		try{
			$this->exec("command -v $command");
			return true;
		}catch(\Exception $e){
			return false;
		}
	}

	public function sudo(?string $command='echo'): CLI
	{
		if($this->isRoot === false){
			$command = "sudo $command";
		}

		$this->exec($command);

		return $this;
	}

	public function getStdErr(): string
	{
		return self::$stderr;
	}

	public function getErrorCode(): int
	{
		return self::$exitCode;
	}

	public function exec(string $command, bool $firstLine=false, bool $throw=true)
	{
		$debug = "{red}[EXEC]:{end} $command";

		unset($pipes);
		$pipes = [];

		$proc = proc_open($command,[
			1 => ['pipe','w'],
			2 => ['pipe','w'],
		],$pipes);

		self::$stdout = trim(stream_get_contents($pipes[1]));
		fclose($pipes[1]);

		self::$stderr = trim(stream_get_contents($pipes[2]));
		fclose($pipes[2]);

		$code = proc_close($proc);

		self::$exitCode = $code;

		$debug = "$debug, {blu}Return Code:{end} $code";
		$debug = "$debug, {blu}Error Output:{end} '".self::$stderr."'";

		$this->debug($debug);

		if($code !== 0 && $throw === true){
			throw new \Exception(self::$stdout." ".self::$stderr, $code);
		}

		$output = empty(self::$stdout) ? [""] : explode("\n", self::$stdout);

		return $firstLine ? current($output) : $output;
	}

	public function passthru(string $command, bool $throw=true): int
	{
		$this->debug("{red}[PASSTHRU]:{end} $command");

		$redirect = $this->statusChannel('debug') ? "" : "2>&1";

		passthru("$command $redirect", $code);

		self::$exitCode = $code;

		if ($code !== 0 && $throw === true){
			throw new \Exception(__METHOD__.": error with command '$command'\n");
		}

		return $code;
	}

	public function print(?string $string=''): string
	{
		if(empty($string)) return '';

		return $this->writeChannel('stdout', $string);
	}

	public function debug(?string $string='')
	{
		if(empty($string)) return '';

		return $this->writeChannel('debug', '{blu}[DEBUG]:{end} '.trim($string)."\n");
	}

	public function quiet(?string $string='')
	{
		if(empty($string)) return '';

		return $this->writeChannel('quiet', $string);
	}

	public function success(?string $string=null)
	{
		$this->die($string, 0);
	}

	public function failure(?string $string=null)
	{
		$this->die($string, 1);
	}

	public function ping(Address $address): void
	{
		if($address->ping()){
			$output[] = "Ping: {grn}SUCCESS{end}";
		}else{
			$output[] = "Ping: {red}FAILURE{end}";
		}

		if($address->hostname !== null){
			$output[] = "Hostname: '{yel}{$address->hostname}{end}'";
		}

		if($address->ip_address !== null){
			$output[] = "IP Address: '{yel}{$address->ip_address}{end}'";
		}

		if($address->packet_loss === 0.0 && $address->can_resolve === true){
			$output[] = "Status: {grn}SUCCESS{end}";
		}else{
			$output[] = "Status: {red}FAILURE{end}";
		}

		if($address->can_resolve === true){
			$output[] = "Can Resolve: {grn}YES{end}";
		}else{
			$output[] = "Can Resolve: {red}NO{end}";
		}

		$this->print(implode(", ", $output) . "\n");
	}

	public function die(?string $string=null, int $exitCode=0)
	{
		$colour	= $exitCode === 0 ? "{grn}" : "{red}";
		$where	= $exitCode === 0 ? STDOUT : STDERR;

		if($string !== null){
			fwrite($where, $this->text->write($colour.rtrim($string, "\n")."{end}\n"));
		}

		exit($exitCode);
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
