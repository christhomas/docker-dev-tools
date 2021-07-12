<?php declare(strict_types=1);

namespace DDT\Helper;

class Arr
{
	static public function hasAll(array $array, array $key, bool $allowEmpty=true): bool
	{
		foreach($key as $k){
			if(self::has($array, $k, $allowEmpty) === false){
				return false;
			}
		}

		return true;
	}

	static public function has($array, $key, $allowEmpty=true): bool
	{
		$key = strpos($key, '.') !== false ? explode('.', $key) : [$key];

		foreach($key as $find){
			if(!is_array($array)) return false;

			// key doesn't exist at all
			if(!array_key_exists($find, $array)) return false;

			// key was found, but notEmpty is enabled and it was empty
			if($allowEmpty === false && empty($array[$find])) return false;

			$array = $array[$find];
		}

		return true;
	}

	static public function get($array, $key)
	{
		if(!is_array($array)) return null;

		$key = strpos($key, '.') !== false ? explode('.', $key) : [$key];

		foreach($key as $find){
			if(!array_key_exists($find, $array)) {
				return null;
			}

			$array = $array[$find];
		}

		return $array;
	}
}
