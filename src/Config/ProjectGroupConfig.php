<?php declare(strict_types=1);

namespace DDT\Config;

use DDT\Config\External\ComposerProjectConfig;
use DDT\Config\External\NpmProjectConfig;
use DDT\Config\External\StandardProjectConfig;
use DDT\Exceptions\Project\ProjectNotFoundException;

class ProjectGroupConfig
{
	private $key = 'projects';

	/** @var SystemConfig $config */
	private $config;

	public function __construct(SystemConfig $config)
	{
		$this->config = $config;

		// NOTE: Don't use listGroup() here, we must ensure it's never null in the first place
		if($this->config->getKey($this->key) === null){
			$this->config->setKey($this->key, []);
		}
	}

	public function listGroup(): array
	{
		return $this->config->getKey($this->key);
	}

	public function hasGroup(string $name): bool
	{
		$list = $this->listGroup();

		return array_key_exists($name, $list);
	}

	public function addGroup(string $name): bool
	{
		if($this->hasGroup($name)){
			return false;
		}
		
		$groups = $this->listGroup();
		$groups[$name] = [];
		$this->config->setKey($this->key, $groups);

		return $this->config->write();
	}

	public function removeGroup(string $name): bool
	{
		if($this->hasGroup($name)){
			$groups = $this->listGroup();
			
			if(array_key_exists($name, $groups)){
				unset($groups[$name]);
				$this->config->setKey($this->key, $groups);

				return $this->config->write();
			}
		}

		return false;
	}

	public function addProject(string $group, string $project, string $dir, ?string $type, ?string $repo, ?string $remote): bool
	{
		if($this->hasGroup($group)){
			$groupKey = "{$this->key}.$group";
			$group = $this->config->getKey($groupKey);
			
			$group[$project] = [
				"path" => $dir,
				"repo" => [
					"url" => $repo,
					"remote" => $remote
				],
				"type" => $type,
			];
			
			if(empty($repo)){
				unset($group[$project]["repo"]);
			}

			$this->config->setKey($groupKey, $group);

			return $this->config->write();
		}

		return false;
	}

	public function removeProject(string $group, string $project): bool
	{
		if($this->hasGroup($group)){
			$groupKey = "{$this->key}.$group";
			$group = $this->config->getKey($groupKey);

			if(array_key_exists($project, $group)){
				unset($group[$project]);
				$this->config->setKey($groupKey, $group);

				return $this->config->write();
			}
		}

		return false;
	}

	public function getProjectDirectory(string $group, string $project): ?string
	{
		if($this->hasGroup($group)){
			$groupKey = "{$this->key}.$group";
			$group = $this->config->getKey($groupKey);

			if(array_key_exists($project, $group)){
				return $group[$project]['path'];
			}
		}

		return null;
	}

	public function getProjectConfig(string $group, string $project): StandardProjectConfig
	{
		if($this->hasGroup($group)){
			$groupKey = "{$this->key}.$group";
			$groupList = $this->config->getKey($groupKey);

			if(array_key_exists($project, $groupList)){
				$type = $groupList[$project]['type'] ?? "ddt";
				$path = $groupList[$project]['path'];
				$config = null;

				if($type === "ddt"){
					$config = new StandardProjectConfig($path);
				}
				
				if($type === "npm"){
					$config = new NpmProjectConfig($path);
				}
				
				if($type === "composer"){
					$config = new ComposerProjectConfig($path);
				}

				if($config instanceof StandardProjectConfig){
					return $config;
				}
			}
		}

		throw new ProjectNotFoundException($group, $project);
	}
}