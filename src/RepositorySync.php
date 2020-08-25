<?php
class RepositorySync
{
    private $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function push(?string $filter = null): void
	{
		$this->sync("push", $filter);
	}

	public function pull(?string $filter = null): void
	{
		$this->sync("pull", $filter);
	}

	public function sync(string $direction, ?string $filter = null): void
	{
		$list = $this->listProjects($filter);

		$projects = [];

		foreach($list as $dir){
			$data = $this->getProjectStatus($dir);

			if(!array_key_exists("exception", $data)){
				if($data["changes"] === "no"){

				}
			}else{

			}
		}

		var_dump($list);
		die("DEAD");
	}

	public function listProjects(?string $filter = null): array
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

	public function listBranches(?string $filter = null): array
	{
		$list = $this->listProjects($filter);

		$projects = [];

		foreach($list as $name => $dir){
			try{
				$status = $this->getProjectStatus($dir);

				$projects[$name] = [
					"branch" => $status["branch"],
					"changes" => $status["changes"],
				];
			}catch(Exception $e){
				$projects[$name] = [
					"exception" => true
				];
			}
		}

		return $projects;
	}

	public function getProjectStatus(string $dir): array
	{
		$git = "git -C ${dir}";
		$status = Shell::exec("$git status -s");

		return [
			"changes"	=> empty($status) ? "no" : "yes",
			"branch"	=> Shell::exec("$git rev-parse --abbrev-ref HEAD", true),
		];
	}

    public function listHookNames(): array
    {
        $list = $this->config->getKey("project_sync.hooks");

        return array_keys($list);
    }

    public function listHook(string $name): array
    {
        return $this->config->getKey("project_sync.hooks.$name");
    }

    public function parseHook(string $name, array $tokens = []): array
    {
        $list = $this->listHook($name);

        foreach($list as $i => $s){
            foreach($tokens as $t => $r){
                $s = str_replace("{".$t."}", $r, $s);
            }

            if(preg_match("/({file}((?:.|\n)*?){\/file})/", $s, $matches) !== false){
                if(!empty($matches)){
                    if(!file_exists($matches[2])){
                        $s = null;
                    }else{
                        $s = str_replace($matches[1], $matches[2], $s);
                    }
                }
            }

            $list[$i] = $s;
        }

        return array_filter($list);
    }

    public function addHook(string $name, string $script): bool
    {
        $hook = $this->config->getKey("project_sync.hooks.$name");
        $hook[] = $script;

        $this->config->setKey("project_sync.hooks.$name", array_unique(array_values($hook)));
        return $this->config->write();
    }

    public function removeHook(string $name, int $index): bool
    {
        $hook = $this->config->getKey("project_sync.hooks.$name");
        unset($hook[$index]);

        $this->config->setKey("project_sync.hooks.$name", array_unique(array_values($hook)));
        return $this->config->write();
    }
}
