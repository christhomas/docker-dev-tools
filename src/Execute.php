<?php
class Execute
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
			Execute::run("command -v $command");
			return true;
		}catch(Exception $e){
			return false;
		}
	}

	static public function exec(string $command, bool $outputOptimise=false)
	{
		if(self::$debug){
			print(Text::blue("[DEBUG] Run command: ").$command."\n");
		}

		$redirect = self::$debug ? "" : "2>&1";

		exec("$command $redirect", $output, $code);
		self::$last_error = $code;

		if($code !== 0){
			throw new Exception(implode("\n", $output),$code);
		}

		return $outputOptimise ? current($output) : $output;
	}

	static public function run(string $command, bool $outputOptimise=false)
	{
		return self::exec($command, $outputOptimise);
	}

	static public function passthru($command): int
	{
		if(self::$debug){
			print(Text::blue("[DEBUG] Passthru command: ").$command."\n");
		}

		$redirect = self::$debug ? "" : "2>&1";

		passthru("$command $redirect", $code);
		self::$last_error = $code;

		if ($code !== 0){
			throw new Exception(__METHOD__.": error with command '$command'\n");
		}

		return $code;
	}
}
