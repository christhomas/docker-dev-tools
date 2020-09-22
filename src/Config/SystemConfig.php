<?php

class SystemConfig extends BaseConfig
{
    private $extensions;
	private $projects;
	
	const FILENAME = ".ddt-system.json";

    static public function getDefaultFilename(): string
    {
        return $_SERVER['HOME'] . '/' . self::FILENAME;
    }

    public function __construct(?string $filename=null)
    {
        $filename = $filename ?: self::getDefaultFilename();
        
        try{
            parent::__construct($filename);
        }catch(ConfigMissingException $e){
            die(Text::box("The config file must exist: '$filename'", "white", "red"));
        }catch(ConfigInvalidException $e){
            die(Text::box("The config file was invalid, it could not be decoded: '$filename'", "white", "red"));
        }
    }

    public function setToolsPath(string $path): void
	{
        $this->setKey('path.tools', $path);
    }
    
    public function getToolsPath(string $subpath = null): string
	{
		$path = $this->getKey("path.tools");
		$path = $path ?: dirname(__DIR__);

		return $path . $subpath;
	}

	public function setProjectPath(string $path): void
	{
		$this->setKey('path.projects', $path);
	}

	public function getProjectPath(string $subpath = null): string
	{
		$path = $this->getKey('path.projects');
		$path = $path ?: dirname($this->getToolsPath());

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