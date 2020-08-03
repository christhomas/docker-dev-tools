<?php
class Shell
{
	protected static $debug = false;
	protected static $last_error = 0;

	static public function setDebug($state)
	{
		self::$debug = !!$state;
	}

	static public function getError()
	{
		return self::$last_error;
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

		$stdout = trim(stream_get_contents($pipes[1]));
		fclose($pipes[1]);

		$stderr = trim(stream_get_contents($pipes[2]));
		fclose($pipes[2]);

		$code = proc_close($proc);

		self::$last_error = $code;

		if($code !== 0 && $throw === true){
			throw new Exception("$stdout $stderr",$code);
		}

		$stdout = empty($stdout) ? [] : explode("\n", $stdout);

		return $firstLine ? current($stdout) : $stdout;
	}

	static public function passthru(string $command, bool $throw=true): int
	{
		if(self::$debug){
			print(Text::blue("[DEBUG] Passthru command: ").$command."\n");
		}

		$redirect = self::$debug ? "" : "2>&1";

		passthru("$command $redirect", $code);
		self::$last_error = $code;

		if ($code !== 0 && $throw === true){
			throw new Exception(__METHOD__.": error with command '$command'\n");
		}

		return $code;
	}
}
