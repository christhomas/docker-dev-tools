<?php

class Text
{
	const TERMINATE_CONTROL_CHAR = "\033[0m";

	static private $fullNameMap = [
		"blk"=>"black",
		"red"=>"red",
		"grn"=>"green",
		"yel"=>"yellow",
		"blu"=>"blue",
		"mag"=>"magenta",
		"cyn"=>"cyan",
		"wht"=>"white"
	];

	static private $codes = [
		# foreground colours
		'blk'	=>	"\033[30m",
		'red'	=>	"\033[31m",
		'grn'	=>	"\033[32m",
		'yel'	=>	"\033[33m",
		'blu'	=>	"\033[34m",
		'mag'	=>	"\033[35m",
		'cyn'	=>	"\033[36m",
		'wht'	=>	"\033[37m",

		# background colours
		'blk_b'	=>	"\033[40m",
		'red_b'	=>	"\033[41m",
		'grn_b'	=>	"\033[42m",
		'yel_b'	=>	"\033[43m",
		'blu_b'	=>	"\033[44m",
		'mag_b'	=>	"\033[45m",
		'cyn_b'	=>	"\033[46m",
		'gry_b'	=>	"\033[47m",

		# reset whatever terminal stuff you've done
		'end'	=>	"\033[0m",

		# Some icons
		'chk_i'	=>	"\xE2\x9C\x85",
		'mss_i'	=>	"\xE2\x9D\x8C",
		'wrn_i'	=>	"\xF0\x9F\x98\xB1",
	];

	static private $quiet = false;

	static public function initColours()
	{
		foreach(self::$codes as $key => $value)
		{
			$printing = (strpos($key, "_i") === (strlen($key) - 2)) ? 2 : 0;

			self::$codes[$key] = ['printing' => $printing, 'value' => $value];

			$key = str_replace(array_keys(self::$fullNameMap), array_values(self::$fullNameMap), $key);

			self::$codes[$key] = ['printing' => $printing, 'value' => $value];
		}
	}

	static public function setQuiet(bool $active): void
	{
		self::$quiet = $active;
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

	static public function stripQuiet(string $input, bool $ignore=false): string
	{
		if(preg_match_all("/({quiet}((?:.|\n)*?){\/quiet})/", $input, $matches) !== false){
			if(self::$quiet === true && $ignore === false){
				$input = str_replace($matches[0], "", $input);
			}else{
				$input = str_replace($matches[0], $matches[2], $input);
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
		foreach(self::$codes as $key => $code){
			$string = str_replace('{'.strtolower($key).'}',$code['value'],$string);
		}

		return self::stripQuiet($string, $ignoreQuiet);
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
		return self::write("{" . $foreground . "}{" . $background . "_b}\n\t\n\t" . trim($string) . "\n{end}\n\n");
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
