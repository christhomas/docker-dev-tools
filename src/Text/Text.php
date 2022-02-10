<?php declare(strict_types=1);

namespace DDT\Text;

class Text
{
	const TERMINATE_CONTROL_CHAR = "\033[0m";

	private $codes = [];

	public function __construct()
	{
		$colours = parse_ini_file(__DIR__ . '/../../lib/colours.ini');

		foreach($colours as $key => $val){
			if(strpos($val, '\033') === 0){
				$val = "\e".str_replace('\033', '', $val);
			}

			if(strpos($val, "\x") === 0){
				$val = array_filter(explode("\x", $val));
				$val = hex2bin(implode("", $val));
			}

			$this->addCode($key, $val);
		}
	}

	public function addCode($key, $value): void
	{
		$key = strtolower($key);

		$printing = strpos($key, "i_") !== false ? 2 : 0;

		$this->codes[$key] = ['key' => $key, 'printing' => $printing, 'value' => $value, 'length' => strlen($value)];
	}

	public function findCodes($string): array
	{
		$codes = [];

		foreach($this->codes as $code){
			$count = substr_count($string, $code['value']);
			$codes = $codes + array_fill(count($codes), $count, $code);
		}

		return $codes;
	}

	/** @deprecated */
	public function stripColours(string $input): string
	{
		return $this->stripCodes($input);
	}

	public function stripCodes(string $input): string
	{
		foreach($this->codes as $key){
			$input = str_replace($key['value'], '', $input);
		}
		return $input;
	}

	public function dump(): string
	{
		$args = func_get_args();

		if(count($args) === 0){
			print($this->write(
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

	public function write(string $string): string
	{
		foreach($this->codes as $key => $code){
			$string = str_replace('{'.$key.'}', $code['value'], $string);
		}

		return $string;
	}

	public function white(string $string): string
	{
		return $this->write("{wht}$string{end}");
	}

	public function green(string $string): string
	{
		return $this->write("{grn}$string{end}");
	}

	public function red(string $string): string
	{
		return $this->write("{red}$string{end}");
	}

	public function blue(string $string): string
	{
		return $this->write("{blu}$string{end}");
	}

	public function yellow(string $string): string
	{
		return $this->write("{yel}$string{end}");
	}

	public function box(string $string, string $foreground, string $background): string
	{
		return $this->write("{" . $foreground . "}{" . $background . "_b}\n\t\n\t" . trim($string) . "\n{end}\n");
	}
}
