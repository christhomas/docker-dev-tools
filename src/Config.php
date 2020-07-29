<?php
class Config
{
	private $data = [];
	private $filename = null;

	const CONFIG_OK = "This config is ok";
	const CONFIG_MISSING = "The config file must exist: '%s'";
	const CONFIG_INVALID = "The config file was invalid, it could not be decoded: '%s'";

	public function __construct(?string $filename=null)
	{
		$result = $this->setFilename($filename);
		if($result !== self::CONFIG_OK){
			throw new Exception(sprintf($result, $filename));
		}

		$result = $this->read($this->getFilename());
		if($result !== self::CONFIG_OK){
			throw new Exception(sprintf($result, $filename));
		}

//		if($this->isTopLevel()){
//			if($this->getHost() === null){
//				Script::die("The toplevel config has no host data, it must contain this element to be valid");
//			}
//		}
//
//		if(!array_key_exists('projects', $this->data[$this->filename])){
//			$this->data[$this->filename] = [];
//		}
	}

	public function getType(): string
	{
		if(!array_key_exists('type', $this->data[$this->filename])){
			throw new Exception("Every config must have a type. If this is a toplevel style, add type=>toplevel to the top of json file");
		}

		return $this->data[$this->filename]['type'];
	}

	public function isTopLevel(): bool
	{
		return $this->getType() === "toplevel";
	}

	public function getToolsPath(string $subpath = null): string
	{
		$path = ArrayWrapper::get($this->data[$this->filename], 'path');
		$path = $path ?: dirname(__DIR__);

		return $path . $subpath;
	}

	public function setToolsPath(string $path): void
	{
		$this->data[$this->filename]['path'] = $path;
	}

	public function setFilename(?string $filename)
	{
		$filename = $filename ?: $_SERVER['HOME'] . '/.ddt-config.json';

		$filename = realpath($filename);

		if(!file_exists($filename)){
			return self::CONFIG_MISSING;
		}

		$this->filename = $filename;

		return self::CONFIG_OK;
	}

	public function getFilename(): string
	{
		return $this->filename;
	}

	public function getVersion(): string
	{
		return ArrayWrapper::get($this->data[$this->filename], "version");
	}

	private function read($filename): string
	{
		if(array_key_exists($filename, $this->data)){
			return self::CONFIG_OK;
		}

		if(file_exists($filename) === false){
			return self::CONFIG_MISSING;
		}

		$contents = file_get_contents($filename);

		$json = json_decode($contents, true);

		if(empty($json)){
			return self::CONFIG_INVALID;
		}

		$this->data[$filename] = $json;

		return self::CONFIG_OK;
	}

	public function write(?string $filename=null): bool
	{
		$filename = $filename ?: $this->filename;

		$data = json_encode($this->data[$this->filename], JSON_PRETTY_PRINT);

		return file_put_contents($filename, $data);
	}

	public function setKey(string $key, $value): void
	{
		$list = explode(".", $key);

		$parent = &$this->data[$this->filename];

		foreach($list as $listIndex => $keyName){
			if($listIndex === count($list)-1) {
				$parent[$keyName] = $value;
			}else if(!array_key_exists($keyName, $parent)){
				$parent[$keyName] = [];
			}

			if(array_key_exists($keyName, $parent)){
				unset($copy);
				$copy = &$parent[$keyName];
				unset($parent);
				$parent = &$copy;
			}
		}

		unset($parent, $copy);
	}

	public function getKey(string $key)
	{
		$data = $key !== "."
			? $this->scanConfigTree($key)
			: $this->data[$this->filename];

		if(is_array($data) && count($data) === 1) $data = current($data);

		return $data;
	}

	public function getKeyAsJson(string $key): string
	{
		$data = $this->getKey($key);

		return json_encode($data, JSON_PRETTY_PRINT);
	}

	public function testKey(string $key, $value): bool
	{
		try{
			$testConfig = new Config($this->filename);

			$src = $testConfig->getKeyAsJson($key);
			$dst = json_encode($value, JSON_PRETTY_PRINT);

			if($src === $dst) return true;

			return false;
		}catch(Exception $e){
			return false;
		}
	}

	public function scanConfigTree(string $section, ?callable $callback=null): array
	{
		$results = [];

		foreach($this->data as $config){
			$parent = ArrayWrapper::get($config, $section);

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
		}

		return $results;
	}

	public function listExtensions(): array
	{
		return $this->getKey("extensions");
	}

	public function addExtension(string $name, string $url, string $path): bool
	{
		$data = [
			"url" => $url,
			"path" => $path,
		];

		$this->setKey("extensions.$name", $data);

		return count(array_diff($this->getKey("extensions.$name"), $data)) === 0;
	}

	public function removeExtension(string $name): bool
	{
		if($this->getKey("extensions.$name")){
			unset($this->data[$this->filename]["extensions"][$name]);
			return true;
		}else{
			return false;
		}
	}

	public function listHealthcheck(): array
	{
		$list = $this->scanConfigTree("healthchecks", function($key, $value) {
			if(Healthcheck::isHealthcheck($value)){
				return [$key];
			}
		});

		return is_array($list) ? $list : [];
	}

	public function getHealthcheck(string $name): Healthcheck
	{
		$data = $this->scanConfigTree("healthchecks", function($key, $value) use ($name) {
			if($key === $name && Healthcheck::isHealthcheck($value)){
				$value["name"] = $key;
				return [$key => $value];
			}
		});

		return new Healthcheck(current($data));
	}

	public function addProject(string $name, string $git, string $branch): bool
	{
		$this->data[$this->filename]["projects"][$name] = [
			"git" => $git,
			"branch" => $branch
		];

		return $this->hasProject($name);
	}

	public function removeProject(string $name)
	{
		unset($this->data[$this->filename]["projects"][$name]);

		return $this->hasProject($name) === false;
	}

	public function hasProject($name): bool
	{
		return array_key_exists($name, $this->data[$this->filename]["projects"]);
	}

	public function getAwsVaultProfile(): ?string
	{
		$result = $this->scanConfigTree("aws_vault");

		return count($result) ? current($result) : null;
	}

	public function getHost(): ?array
	{
		$result = $this->scanConfigTree("host");

		return count($result) ? current($result) : null;
	}

	public function getBastionHost($name): array
	{
		$result = $this->scanConfigTree("bastion.hosts.$name");

		$config = current($result);

		if($config === null) throw new Exception("Bastion Host '$name' was not configured");

		if(ArrayWrapper::get($config, 'type') === 'aws-ssm'){
			list ($config["host"],$config["port"]) = Aws::getParam([$config["host"],$config["port"]]);
		}

		return $config;
	}

	public function getBastionService($name): array
	{
		$result = $this->scanConfigTree("bastion.services.$name");

		$config = current($result);

		if($config === null) throw new Exception("Bastion Service '$name' was not configured");

		if(ArrayWrapper::get($config, 'type') === 'aws-ssm'){
			list ($config["host"],$config["port"]) = Aws::getParam([$config["host"],$config["port"]]);

			return $config;
		}

		return $config;
	}
}
