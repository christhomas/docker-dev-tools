<?php

class Shell
{
	protected static $debug = false;
	protected static $exitCode = 0;

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
			Shell::exec("command -v $command");
			return true;
		}catch(Exception $e){
			return false;
		}
	}

	static public function printDebug($prefix, $content)
    {
        print(Text::blue("[DEBUG] $prefix: ").$content."\n");
    }

	static public function exec(string $command, bool $firstLine=false, bool $throw=true)
	{
		if(self::$debug){
		    self::printDebug("Run command",$command);
		}

		unset($pipes);
		$pipes = [];

		$proc = proc_open($command,[
			1 => ['pipe','w'],
			2 => ['pipe','w'],
		],$pipes);

		$stdout = trim(stream_get_contents($pipes[1]));
		fclose($pipes[1]);

		$stderr = trim(stream_get_contents($pipes[2]));
		fclose($pipes[2]);

		$code = proc_close($proc);

		self::$exitCode = $code;

        if(self::$debug){
            self::printDebug("Code", $code);
            self::printDebug("StdErr", "'$stderr'");
        }

		if($code !== 0 && $throw === true){
			throw new Exception("$stdout $stderr",$code);
		}

		$stdout = empty($stdout) ? [""] : explode("\n", $stdout);

		return $firstLine ? current($stdout) : $stdout;
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
}
