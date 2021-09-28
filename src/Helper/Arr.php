<?php declare(strict_types=1);

namespace DDT\Helper;

class Arr
{
	private $data;

	public function __construct(array $data)
	{
		$this->data = $data;
	}

	public function has($key, $allowEmpty=true): bool
	{
		$key = strpos($key, '.') !== false ? explode('.', $key) : [$key];

		$array = $this->data;

		foreach($key as $search){
			// key doesn't exist at all
			if(!array_key_exists($search, $array)) return false;

			// key was found, but notEmpty is enabled and it was empty
			if($allowEmpty === false && empty($array[$search])) return false;

			$array = $array[$search];
		}

		return true;
	}

	public function hasAll(array $key, bool $allowEmpty=true): bool
	{
		foreach($key as $k){
			if($this->has($k, $allowEmpty) === false){
				return false;
			}
		}

		return true;
	}

	public function get($key=null)
	{
		$array = $this->data;

		if($key === null) return $array;

		$key = strpos($key, '.') !== false ? explode('.', $key) : [$key];

		foreach($key as $find){
			if(!array_key_exists($find, $array)) {
				return null;
			}

			$array = $array[$find];
		}

		return $array;
	}

	public function pull($key)
	{
		// set the search array to the top level data array
		$array = &$this->data;

		// break key up into segments if there is a dot to explode them by
		$key = strpos($key, '.') !== false ? explode('.', $key) : [$key];

		// The final data needed to correctly pull the requested key from the array data
		$pullKey = null;
		$pullParent = null;
		$pullData = null;

		foreach($key as $segment){
			// Search for segment in search array, otherwise return null
			if(!array_key_exists($segment, $array)) {
				return null;
			}

			// Reset the parent to the current array data and segment
			unset($pullParent);
			$pullParent = &$array;
			$pullKey = $segment;

			// Reset the array value to the subtree, so it can scan into that for the next segment
			unset($array);
			$array = &$pullParent[$pullKey];
		}

		// After all segments were found, the resulting pull parent/key will be what the user expects to be returned
		$pullData = $pullParent[$pullKey];

		// Now we have to remove the key from the array data structure
		unset($pullParent[$pullKey]);

		// unset these references to break the connection to the array data
		unset($pullParent);
		unset($array);

		// then return the subtree the user pulled
		return $pullData;
	}
}
