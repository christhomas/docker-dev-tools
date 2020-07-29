<?php declare(strict_types=1);

class Watcher
{
	private $script;
	private $config;
	private $docker;

	private $profileKey = 'watch_sync.profiles';
	private $ignoreRuleKey = 'watch_sync.ignore_rule';

	public function __construct(string $script, Config $config, Docker $docker)
	{
		$this->script = $script;
		$this->config = $config;
		$this->docker = $docker;

		if(!Shell::isCommand('fswatch')){
			throw new Exception("fswatch is not installed, please install it when requested or do it yourself");
		}
	}

	public function listProfiles(DockerProfile $dockerProfile): array
	{
		$profileList = $this->config->getKey(implode('.',[$this->profileKey,$dockerProfile->getName()]));

		foreach(array_keys($profileList) as $name){
			$profileList[$name] = $this->getProfile($dockerProfile, $name);
		}

		return $profileList;
	}

	public function getProfile(DockerProfile $dockerProject, string $syncProfile): ?DockerSyncProfile
	{
		$profile = $this->config->getKey(implode('.',[$this->profileKey, $dockerProject->getName(), $syncProfile]));

		if(ArrayWrapper::hasAll($profile, ['container', 'local_dir', 'remote_dir'])){
			return new DockerSyncProfile(
				$syncProfile,
				$profile['container'],
				$profile['local_dir'],
				$profile['remote_dir']
			);
		}else{
			throw new Exception("Profile '$syncProfile' was invalid");
		}
	}

	public function addProfile(DockerProfile $dockerProfile, string $syncProfile, string $container, string $localDir, string $remoteDir): bool
	{
		$syncProfile = new DockerSyncProfile($syncProfile, $container, $localDir, $remoteDir);

		$this->config->setKey(implode('.',[$this->profileKey,$dockerProfile->getName(),$syncProfile->getName()]),$syncProfile);
		$this->config->write();

		return true;
	}

	public function removeProfile(DockerProfile $dockerProfile, string $syncProfile): bool
	{
		$profileList = $this->listProfiles($dockerProfile);

		foreach(array_keys($profileList) as $profile){
			if($profile === $syncProfile){
				unset($profileList[$syncProfile]);
				$this->config->setKey(implode('.',[$this->profileKey,$dockerProfile->getName()]), $profileList);
				$this->config->write();

				return true;
			}
		}

		return false;
	}

	public function listIgnoreRules(): array
	{
		return $this->config->getKey($this->ignoreRuleKey);
	}

	public function setIgnoreRules(array $rules): void
	{
		$this->config->setKey($this->ignoreRuleKey, $rules);
		$this->config->write();
	}

	public function addIgnoreRule(string $rule): void
	{
		$list = $this->listIgnoreRules();
		$list[] = $rule;
		$this->setIgnoreRules(array_unique($list));
	}

	public function removeIgnoreRule(string $rule): void
	{
		$list = $this->listIgnoreRules();
		foreach(array_keys($list) as $test){
			if($list[$test] === $rule) unset($list[$test]);
		}
		$this->setIgnoreRules($list);
	}

	public function shouldIgnore(DockerSyncProfile $syncProfile, string $filename): bool
	{
		$list = $this->listIgnoreRules();

		if(strpos($filename, $syncProfile->getLocalDir()) === 0){
			$filename = substr($filename, strlen($syncProfile->getLocalDir()));
		}

		foreach($list as $rule){
			$la		= substr_compare($rule, "^", 0, 1) === 0 ? "^" : "";
			$ra		= substr_compare($rule, "$", -1) === 0 ? "$": "";
			$rule 	= rtrim(ltrim($rule,"^"),"$");
			$rule 	= ltrim($rule, '/');
			$rule 	= preg_quote("/$rule",'/');
			$rule 	= "/".$la.$rule.$ra."/";

			if(preg_match($rule, $filename)){
				return true;
			}
		}

		return false;
	}

	public function watch(DockerProfile $dockerProfile, DockerSyncProfile $syncProfile): bool
	{
		$script = "$this->script --docker={$dockerProfile->getName()} --profile={$syncProfile->getName()}";
		$command = "fswatch {$syncProfile->getLocalDir()} | while read file; do file=$(echo \"\$file\" | sed '/\~$/d'); $script --write=\"\$file\"; done";

		return Shell::passthru($command) === 0;
	}

	public function write(DockerSyncProfile $syncProfile, string $localFilename): bool
	{
		try{
			$container = $syncProfile->getContainer();
			$remoteFilename = $syncProfile->getRemoteFilename($localFilename);

			$temp = "/tmp/".implode('_', [bin2hex(random_bytes(8)), basename($remoteFilename)]);

			$this->docker->exec("cp -a $localFilename $container:$temp");
			$this->docker->exec("exec -i --user=0 $container mv -f $temp $remoteFilename");

			return true;
		}catch(Exception $e){
			return false;
		}
	}
}
