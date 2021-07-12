<?php declare(strict_types=1);

namespace DDT\Config;

use DDT\Helper\Arr;
abstract class BaseConfig implements ConfigInterface
{
    private $data = [];
	private $filename = null;

    public function __construct(string $filename)
	{
		$this->setFilename($filename);
        $this->read();
    }
    
    public function setFilename(string $filename): void
	{
        if(is_dir($filename)){
            $filename = $filename . '/' . $this->getDefaultFilename();
        }

        $filename = realpath($filename);

        if(!is_file($filename)){
            throw new \ConfigMissingException($filename);
		}

		$this->filename = $filename;
	}

	public function getFilename(): string
	{
		return $this->filename;
    }

    public function getName(): string
    {
        return $this->getKey('name');
    }

    public function getType(): string
	{
        $type = $this->getKey('type');

        if($type === null){
			throw new \ConfigInvalidException("Every config must have a type field. If this is a main configuration file, add type=system to the top of json file");
		}

		return $this->data['type'];
    }

    public function setVersion(string $version): void
    {
        $this->setKey('version', $version);
    }
    
    public function getVersion(): string
	{
        $version = $this->getKey('version');

        if($version === null){
            throw new \ConfigInvalidException("Every config must have a version field");
        }

        return $version;
	}

	private function read(): void
	{
        $filename = $this->getFilename();

		if(file_exists($filename) === false){
            throw new \ConfigMissingException($filename);
		}

		$contents = file_get_contents($filename);

		$json = json_decode($contents, true);

		if(empty($json)){
            throw new \ConfigInvalidException($filename);
		}

		$this->data = $json;
	}

	public function write(?string $filename=null): bool
	{
		$filename = $filename ?: $this->getFilename();

		$data = json_encode($this->data, JSON_PRETTY_PRINT);

		return file_put_contents($filename, $data);
    }

    public function scanConfigTree(string $section, ?callable $callback=null): array
	{
		$results = [];

        $parent = Arr::get($this->data, $section);

        if($callback === null){
            if($parent !== null) $results[] = $parent;
        }else{
            if(is_array($parent)){
                foreach($parent as $key => $value){
                    $results = array_merge($results, (array)$callback($key, $value));
                }
            }else{
                if($parent !== null) $results[] = $parent;
            }
        }

		return $results;
    }
    
    public function setKey(string $key, $value): void
	{
        $parts = explode(".", $key);

        $array = &$this->data;
  
        while (count($parts) > 1) {
            $part = array_shift($parts);
        
            if (!isset($array[$part]) or !is_array($array[$part])) {
                $array[$part] = [];
            }
        
            $array = &$array[$part];
        }
        
        $array[array_shift($parts)] = $value;
        unset($array);
	}
    
    public function getKey(string $key)
	{
        if ($key === '.') return $this->data;

        if (array_key_exists($key, $this->data)) {
            return $this->data[$key];
        }

        $array = $this->data;
        
        foreach (explode(".", $key) as $part) {
            if (!is_array($array) or !isset($array[$part])) {
                return null;
            }
        
            $array = $array[$part];
        }
        
        return $array;
    }
    
    public function getKeyAsJson(string $key): string
	{
		$data = $this->getKey($key);

		return json_encode($data, JSON_PRETTY_PRINT);
    }

    public function toJson(): string
    {
        return $this->getKeyAsJson('.');
    }
    
    public function deleteKey(string $key): bool
    {
        $keys = explode('.', $key);

        $array = &$this->data;
        
        while (count($keys) > 1)
        {
            $key = array_shift($keys);
        
            if (isset($array[$key]) && is_array($array[$key])) {
                $array = &$array[$key];
            }
        }
        
        unset($array[array_shift($keys)]);
        unset($array);
        
        return true;
    }
}