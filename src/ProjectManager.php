<?php
class ProjectManager
{
    private $config;

    public function __construct(Config $config, string $name)
    {
        $this->config	= $config;
        $this->name		= $name;
    }

    public function add(string $url, ?string $branch, ?string $dir): bool
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
					$this->config->write();
				}

				return true;
			}

			throw new DirectoryExistsException("An incompatible project with a different configuration exists");
		}

		$git = new Git($path);
		if($git->clone($url)){
			$project = new Project($this->name, $url, $branch, $dir);
			$this->config->addProject($project);
			return $this->config->write();
		}

		return false;
	}

	public function remove(?bool $delete)
	{
		$project = $this->config->getProject($this->name);

		if($project){
			$this->config->removeProject($this->name);
			return $this->config->write();
		}

		return false;
	}
}
