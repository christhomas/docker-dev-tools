<?php declare(strict_types=1);

namespace DDT\Config;

use Exception;

class SystemConfig extends BaseConfig
{
    private $extensions;
	private $projects;

	static public function instance(?string $filename=null, ?bool $readonly=false): SystemConfig
	{
		/** @var SystemConfig */
		$config = container(SystemConfig::class);
		$config->setReadonly($readonly);
		
		if(!empty($filename)){
			$config->read($filename);
		}

		return $config;
	}

	public function getDescription(): string
	{
		return $this->getKey('description');
	}

	public function getDefaultFilename(): string
	{
		return '.ddt-system.json';
	}

	public function setPath(string $name, string $path): void
	{
		$this->setKey('path.$name', $path);
	}

	public function getPath(string $name, ?string $subpath=''): string
	{
		$path = $this->getKey("path.$name");

		if(empty($path)){
			throw new Exception("The path named '$name' could not be found in the configuration");
		}

		return $path . $subpath;
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
		return $this->deleteKey("extensions.$name");
    }

    public function addProject(string $name, string $git, string $branch): bool
	{
        $this->setKey("projects.$name", [
			"git" => $git,
			"branch" => $branch
		]);

		return $this->hasProject($name);
	}

	public function removeProject(string $name): bool
	{
        $this->deleteKey("projects.$name");

		return $this->hasProject($name) === false;
	}

	public function hasProject($name): bool
	{
        return $this->getKey("projects.$name") !== null;
	}
}