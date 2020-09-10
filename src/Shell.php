<?php
class Shell
{
	protected static $debug = false;
	protected static $error = 0;
	protected static $stdout = "";
	protected static $stderr = "";

	static public function setDebug($state)
	{
		self::$debug = !!$state;
	}

	static public function getError()
	{
		return self::$error;
	}

	static public function isCommand($command): bool
	{
		try{
			Shell::exec("command -v $command");
			return true;
		}catch(Exception $e){
			return false;
		}
	}

	static public function exec(string $command, bool $firstLine=false, bool $throw=true)
	{
		if(self::$debug){
			print(Text::blue("[DEBUG] Run command: ").$command."\n");
		}

		$redirect = self::$debug ? "" : "2>&1";

		$proc = proc_open("$command $redirect",[
			1 => ['pipe','w'],
			2 => ['pipe','w'],
		],$pipes);

		self::$stdout = trim(stream_get_contents($pipes[1]));
		fclose($pipes[1]);

		self::$stderr = trim(stream_get_contents($pipes[2]));
		fclose($pipes[2]);

		$code = proc_close($proc);

		self::$error = $code;

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

		self::$error = $code;

		if ($code !== 0 && $throw === true){
			throw new Exception(__METHOD__.": error with command '$command'\n");
		}

		return $code;
	}
}
