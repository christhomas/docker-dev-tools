<?php
class Shell
{
	protected static $debug = false;
	protected static $exitCode = 0;
	protected static $stdout = "";
	protected static $stderr = "";

	static public function setDebug($state)
	{
		self::$debug = !!$state;
	}

	static public function getExitCode()
	{
		return self::$exitCode;
	}

	static public function isCommand($command): bool
	{
		try{
			self::exec("command -v $command");
			return true;
		}catch(Exception $e){
			return false;
		}
	}

	static public function printDebug($prefix, $content)
    {
		print(Text::blue("[DEBUG] $prefix: ").$content."\n");
    }

	static public function sudo()
	{
		return self::exec("sudo echo");
	}

	static public function exec(string $command, bool $firstLine=false, bool $throw=true)
	{
		$debug = "{blu}[DEBUG] Run command:{end} $command";

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

		if(self::$debug){
			Text::print($debug."\n");
		}

		if($code !== 0 && $throw === true){
			throw new Exception(self::$stdout." ".self::$stderr, $code);
		}

		$output = empty(self::$stdout) ? [""] : explode("\n", self::$stdout);

		return $firstLine ? current($output) : $output;
	}

	static public function passthru(string $command, bool $throw=true): int
	{
		if(self::$debug){
			print(Text::blue("[DEBUG] Passthru command: ").$command."\n");
		}

		$redirect = self::$debug ? "" : "2>&1";

		passthru("$command $redirect", $code);

		self::$exitCode = $code;

		if ($code !== 0 && $throw === true){
			throw new Exception(__METHOD__.": error with command '$command'\n");
		}

		return $code;
	}

	static public function sudoExec($command)
	{
		return self::exec("sudo $command");
	}

	static public function sudoPassthru($command)
	{
		return self::passthru("sudo $command");
	}
}
