<?php declare(strict_types=1);

namespace DDT\Config;

use DDT\Helper\Arr;
use DDT\Exceptions\Config\ConfigMissingException;
use DDT\Exceptions\Config\ConfigInvalidException;
use DDT\Exceptions\Config\ConfigReadonlyException;

abstract class BaseConfig implements ConfigInterface
{
    private $data = [];
	private $filename = null;
    private $readonly = false;

    public function __construct(string $filename, bool $readonly=false)
	{
        $this->setReadonly($readonly);
        $this->read($filename);
    }
    
    public function setFilename(string $filename): string
	{
        if(is_dir($filename)){
            $filename = $filename . '/' . $this->getDefaultFilename();
        }

        $temp = realpath($filename);

        if(!$temp){
            throw new ConfigMissingException($filename);
        }

        $filename = $temp;

        if(!is_file($filename)){
            throw new ConfigMissingException($filename);
		}

		$this->filename = $filename;

        return $filename;
	}

    abstract public function getDefaultFilename(): string;

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
			throw new ConfigInvalidException("Every config must have a type field. If this is a main configuration file, add type=system to the top of json file");
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
            throw new ConfigInvalidException("Every config must have a version field");
        }

        return $version;
	}

    public function setReadonly(bool $readonly): void
    {
        $this->readonly = $readonly;
    }

    public function isReadonly(): bool
    {
        return $this->readonly;
    }

	public function read(string $filename): void
	{
        $this->setFilename($filename);

		if(file_exists($filename) === false){
            throw new ConfigMissingException($filename);
		}

		$contents = file_get_contents($filename);

		$json = json_decode($contents, true);

		if(empty($json)){
            throw new ConfigInvalidException($filename);
		}

		$this->data = $json;
	}

	public function write(?string $filename=null): bool
	{
        if($this->isReadonly()){
            throw new ConfigReadonlyException();
        }

        // If provided, reset the filename to the new file, otherwise get the current filename
		$filename = !empty($filename) ? $this->setFilename($filename) : $this->getFilename();

		$data = json_encode($this->data, JSON_PRETTY_PRINT);

		return file_put_contents($filename, $data) !== false;
    }

    public function scanConfigTree(string $section, ?callable $callback=null): array
	{
		$results = [];

        $arr = new Arr($this->data);
        $parent = $arr->get($section);

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
        $key = ltrim($key ?? '', '.');

        $parts = explode(".", $key);

        $array = &$this->data;
  
        while (count($parts) > 1) {
            $part = array_shift($parts);
        
            if (!isset($array[$part]) or !is_array($array[$part])) {
                $array[$part] = [];
            }
        
            $array = &$array[$part];
        }
        
        $topLevelPart = array_shift($parts);
        
        if(empty($topLevelPart)) $array = $value;
        else $array[$topLevelPart] = $value;
        
        unset($array);
	}
    
    public function getKey(?string $key = null)
	{
        $key = ltrim($key ?? '', '.');

        if(empty($key)) return $this->data;

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
    
    public function getKeyAsJson(?string $key = null): string
	{
		$data = $this->getKey($key);

		return json_encode($data, JSON_PRETTY_PRINT);
    }

    public function toJson(): string
    {
        return $this->getKeyAsJson();
    }
    
    public function deleteKey(string $key): bool
    {
        $key = ltrim($key ?? '', '.');

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