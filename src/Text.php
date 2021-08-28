<?php
require_once(__DIR__.'/../lib/colours.php');

class Text
{
	const TERMINATE_CONTROL_CHAR = "\033[0m";

	static private $codes = [];

	static private $quiet = false;

	static private $debug = 'false';

	static public function setQuiet(bool $state): void
	{
		self::$quiet = $state;
	}

	static public function setDebug(string $mode='true'): void
	{
		self::$debug = $mode;
	}

	static public function addCode($key, $value): void
	{
		$printing = strpos($key, "_I") !== false ? 2 : 0;

		self::$codes[$key] = ['printing' => $printing, 'value' => $value];
	}

	static public function findCodes($string): array
	{
		$codes = [];

		foreach(self::$codes as $code){
			$count = substr_count($string, $code['value']);
			$codes = $codes + array_fill(count($codes),$count, $code);
		}

		return $codes;
	}

	static public function stripColours(string $input): string
	{
		foreach(self::$codes as $key){
			$input = str_replace($key['value'], '', $input);
		}
		return $input;
	}

	// TODO: future plan is to allow a generic way to 'strip tags' from output instead of just quiet or debug 
	static public function stripQuiet(string $input, ?bool $strip=true, ?bool $ignore=false): string
	{
		$tag = 'quiet';

		if(preg_match_all("/({".$tag."}((?:.|\n)*?){\/".$tag."})/", $input, $matches) !== false){
			$result = $strip === true && $ignore === false ? "" : $matches[2];
			$input = str_replace($matches[0], $result, $input);
		}

		return $input;
	}

	static public function stripDebug(string $input, ?string $mode='true'): string
	{
		// TODO: future plan is to allow this to accept optional states, like 'verbose'
		$tag = 'debug';
		
		if(preg_match_all("/(\{".$tag."\}((?:.|\n)*?){\/".$tag."})/", $input, $matches) !== false){
			foreach($matches[0] as $k => $v){
				$result = $mode === 'false' ? "" : "{blu}[DEBUG]:{end} " . $matches[2][$k];
				$input = str_replace($v, $result, $input);
			}
		}

		return $input;
	}

	static public function prefixLines(string $prefix, array $lines, string $separator="\n", string $terminate="\n", string $colour="blue")
	{
		if(!$lines) $lines = ["<EMPTY STRING>"];

		if(!is_array($lines)) $lines = [$lines];

		$lines = array_map(function($entry) use ($colour,$prefix) {
			return call_user_func("self::$colour",$prefix).$entry;
		}, $lines);

		if($separator == false) return $lines;
		if(is_string($separator)) return implode($separator,$lines).$terminate;

		return $lines;
	}

	static public function dump(): string
	{
		$args = func_get_args();

		if(count($args) === 0){
			print(self::write(
				"\n".
				"dump(): called with no arguments?\n".
				"Why are you like this? You don't have to be this way.....\n",
				"cyan"
			));
		}

		if(count($args) == 1) $args = array_shift($args);

		ob_start();
		var_dump($args);
		return ob_get_clean();
	}

	static public function checkIcon(): string
	{
		return constant('chk_i');
	}

	static public function crossIcon(): string
	{
		return constant('mss_i');
	}

	static public function warnIcon(): string
	{
		return constant('wrn_i');
	}

	static public function write(string $string, bool $ignoreQuiet = false): string
	{
		$string = self::stripDebug($string, self::$debug);
		$string = self::stripQuiet($string, self::$quiet, $ignoreQuiet);

		foreach(self::$codes as $key => $code){
			$string = str_replace('{'.strtolower($key).'}',$code['value'],$string);
		}
		
		// TODO: future idea to allow a variable mode of output to the terminal
		// $string = self::stripTag(['debug=verbose', 'debug'], $string, self::$debug);

		return $string;
	}

	static public function white(string $string): string
	{
		return self::write("{wht}$string{end}");
	}

	static public function green(string $string): string
	{
		return self::write("{grn}$string{end}");
	}

	static public function red(string $string): string
	{
		return self::write("{red}$string{end}");
	}

	static public function blue(string $string): string
	{
		return self::write("{blu}$string{end}");
	}

	static public function yellow(string $string): string
	{
		return self::write("{yel}$string{end}");
	}

	static public function box(string $string, string $foreground, string $background): string
	{
		return self::write("{" . $foreground . "}{" . $background . "_b}\n\t\n\t" . trim($string) . "\n{end}\n");
	}

	static public function print(string $string, ?string $foreground=null, ?string $background=null): void
	{
		if($background) $string = "{" . $background . "}$string";
		if($foreground) $string = "{" . $foreground . "}$string";
		if($foreground || $background) $string = "$string{end}";

		print(self::write($string));
	}

	static public function printLoud(string $string, ?string $foreground=null, ?string $background=null): void
	{
		if($background) $string = "{" . $background . "}$string";
		if($foreground) $string = "{" . $foreground . "}$string";
		if($foreground || $background) $string = "$string{end}";

		print(self::write($string, true));
	}
}
