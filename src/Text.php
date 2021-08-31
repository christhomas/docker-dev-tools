<?php declare(strict_types=1);

namespace DDT;
class Text
{
	const TERMINATE_CONTROL_CHAR = "\033[0m";

	static private $codes = [];

	public function __construct()
	{
		$colours = parse_ini_file(__DIR__ . '/../lib/colours.ini');

		foreach($colours as $key => $val){
			if(strpos($val, '\033') === 0){
				$val = "\e".str_replace('\033', '', $val);
			}

			if(strpos($val, "\x") === 0){
				$val = array_filter(explode("\x", $val));
				$val = hex2bin(implode("", $val));
			}

			$key = strtoupper($key);
			define($key, $val);

			if(!in_array($key, ['chk', 'mss', 'wrn'])){
				self::addCode($key, $val);

				$map = [
					"BLK"=>"BLACK",
					"RED"=>"RED",
					"GRN"=>"GREEN",
					"YEL"=>"YELLOW",
					"BLU"=>"BLUE",
					"MAG"=>"MAGENTA",
					"CYN"=>"CYAN",
					"WHT"=>"WHITE"
				];

				$key = str_replace(array_keys($map), array_values($map), $key);
				self::addCode($key, $val);
			}
		}
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

	static public function write(string $string): string
	{
		foreach(self::$codes as $key => $code){
			$string = str_replace('{'.strtolower($key).'}',$code['value'],$string);
		}

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
}
