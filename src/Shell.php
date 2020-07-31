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

		exec("$command $redirect", $output, $code);
		self::$last_error = $code;

		if($code !== 0 && $throw === true){
			throw new Exception(implode("\n", $output),$code);
		}

		return $firstLine ? current($output) : $output;
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
