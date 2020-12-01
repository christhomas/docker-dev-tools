<?php
class ProjectManager
{
    private $config;
    private $name;

    public function __construct(SystemConfig $config, string $name)
    {
        $this->config	= $config;
		$this->name		= $name;
    }

    public function install(string $project, string $url): bool
    {
        $repo = new Git();
        $repo->exists($this->config->getToolsPath());
        return false;
    }

    public function uninstall(string $project): bool
    {
        return false;
    }

	static public function list(?string $filter = null): array
	{
		$list = glob(CLI::getToolPath("/../**/.git"), GLOB_ONLYDIR);

		foreach($list as $key => $value)
		{
			$dir = dirname(realpath($value));
			$list[basename($dir)] = $dir;
			unset($list[$key]);
		}

		$results = array_map(function($dir) use ($filter){
			if($filter === "true") return $dir;

			if(!empty($filter) && strpos(basename($dir), $filter) === false){
				return false;
			}

			return $dir;
		}, $list);

		return array_filter($results);
	}

	public function add(string $url, ?string $branch, ?string $dir): ?Project
	{
		$basePath	= $this->config->getProjectPath();
		$dir		= $dir ?? $this->name;
		$branch		= $branch ?? 'master';
		$path		= "$basePath/$dir";

		// path already exists
		if(is_dir($path)){
			$git = new Git($path);
			$remote = $git->remote();

			// this path already is a git repository for this project
			if($url === $remote){
				// this project is already installed
				$project = $this->config->getProject($this->name);

				if(!$project){
					$project = new Project($this->name, $url, $branch, $dir);
					$this->config->addProject($project);
					if($this->config->write()){
						return $project;
					}
				}else{
					return $project;
				}
			}

			throw new DirectoryExistsException("An incompatible project with a different configuration exists");
		}

		$git = new Git();
		if($git->clone($url, $path)){
			$project = new Project($this->name, $url, $branch, $dir);
			$this->config->addProject($project);
			if($this->config->write()){
				return $project;
			}
		}

		return null;
	}

	public function remove(?bool $delete): bool
	{
		$project = $this->config->getProject($this->name);

		if($project){
			$this->config->removeProject($this->name);
			return $this->config->write();
		}

		return false;
	}

	public function import(): bool
	{
		$basePath	= $this->config->getProjectPath();
		$path		= "$basePath/$this->name";

		$git = new Git($path);
		$url = $git->remote();
		$branch = $git->branch();

		$project = new Project($this->name, $url, $branch, $this->name);

		if($this->config->addProject($project)){
			return $this->config->write();
		}

		return false;
	}
}
