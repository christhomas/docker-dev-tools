<?php declare(strict_types=1);

class Docker
{
	private $config;
	private $command;
	private $profile;
	private $key = "docker";

	public function __construct(Config $config)
	{
		$this->config = $config;
		$this->profile = 'default';
		$this->command = 'docker';

		if(Shell::isCommand('docker') === false){
			Script::failure("Docker is required to run this tool, please install it\n");
		}
	}

	static public function isRunning(): bool
	{
		try{
			Shell::exec("docker version &>/dev/null");
			return true;
		}catch(Exception $e){
			return false;
		}
	}

	static public function pull(string $image): int
	{
		return Shell::passthru("docker pull $image");
	}

	static public function findContainer(string $container): bool
	{
		$list = Shell::exec("docker container ls");

		foreach($list as $line){
			if(strpos($line, $container) !== false){
				return true;
			}
		}

		return false;
	}

	static public function deleteContainer(string $container): bool
	{
		try{
			Shell::exec("docker container rm $container 2>&1");
			Shell::exec("docker rm $container 2>&1");

			return true;
		}catch(Exception $e){
			return false;
		}
	}

	static public function pruneContainer(): void
	{
		Shell::exec("docker container prune -f &>/dev/null");
	}

	static public function findRunning(string $image): ?string
	{
		try{
			$output = Shell::exec("docker ps --no-trunc");

			array_shift($output);

			foreach($output as $line){
				if(preg_match("/^([^\s]+)\s+([^\s]+)/", $line, $matches)){
					if($image === $matches[2]){
						return $matches[1];
					}
				}
			}
		}catch(Exception $e){
			// catch exception, return null
		}

    	return null;
	}

	static public function run(string $image, string $name, array $ports, array $volumes, bool $restart=true): ?string
	{
		$command = ["docker run -d"];

		if($restart) $command[] = '--restart always';

		foreach($ports as $p){
			$command[] = "-p $p";
		}

		foreach($volumes as $v){
			$command[] = "-v $v";
		}

		$command[] = '--name '.$name;
		$command[] = $image;

		try{
			return Shell::exec(implode(" ", $command), true);
		}catch(Exception $e){
			if(strpos($e->getMessage(), "address already in use") !== false){
				Script::failure("Something is already using port 80 on this machine, please stop any local nginx or apache services");
			}

			return null;
		}
	}

	public function logs(string $container): int
	{
		return Shell::passthru("docker logs $container");
	}

	public function logsFollow(string $container): int
	{
		return Shell::passthru("docker logs -f $container");
	}

	static public function getNetworkId(string $network): ?string
	{
		if(empty($network)) return null;

		try{
			$networkId = Shell::exec("docker network inspect $network -f '{{ .Id }}' 2>/dev/null", true);
		}catch(Exception $e){
			$networkId = null;
		}

		return !empty($networkId) ? $networkId : null;
	}

	public function createNetwork(string $network)
	{
		$networkId = Docker::getNetworkId($network);

		if(empty($networkId)){
			$networkId = Shell::exec("docker network create $network", true);
			Text::print("{blu}Create Network:{end} '$network', id: '$networkId'\n");
		}else{
			Text::print("{yel}Network '$network' already exists{end}\n");
		}

		return $networkId;
	}

	static public function deleteNetwork(string $network)
	{
		// TODO
	}

	static public function inspect($type, $name): array
	{
		try{
			$result = Shell::exec("docker $type inspect $name -f '{{json .}}'");
			$result = implode("\n",$result);

			return json_decode($result, true);
		}catch(Exception $e){
			return [];
		}
	}

	public function connectNetwork($network, $containerId): ?bool
	{
		try{
			$networkData = Docker::inspect('network', $network);

			foreach(array_keys($networkData['Containers']) as $id){
				if($id === $containerId) return false;
			}

			Shell::exec("docker network connect $network $containerId");

			return true;
		}catch(Exception $e){
			return null;
		}
	}

	public function disconnectNetwork(string $network, string $containerId): ?bool
	{
		try{
			Shell::exec("docker network disconnect $network $containerId");
			return true;
		}catch(Exception $e){
			return false;
		}
	}

	public function addProfile(string $name, string $host, int $port, ?string $tlscacert, ?string $tlscert, ?string $tlskey): bool
	{
		$profile = new DockerProfile($name, $host, $port, $tlscacert, $tlscert, $tlskey);

		$this->config->setKey("$this->key.$name", $profile);

		return $this->config->write();
	}

	public function removeProfile(string $name): bool
	{
		$profileList = $this->listProfiles();

		foreach(array_keys($profileList) as $profile){
			if($profile === $name){
				unset($profileList[$name]);

				$this->config->setKey($this->key, $profileList);
				$this->config->write();

				return true;
			}
		}

		return false;
	}

	public function getProfile(string $name): ?DockerProfile
	{
		$profile = $this->config->getKey("$this->key.$name");

		if(ArrayWrapper::hasAll($profile, ['host', 'port','tlscacert','tlscert','tlskey'])){
			return new DockerProfile(
				$name,
				$profile['host'],
				(int)$profile['port'],
				$profile['tlscacert'],
				$profile['tlscert'],
				$profile['tlskey']
			);
		}

		return null;
	}

	public function listProfiles(): array
	{
		$profileList = $this->config->getKey($this->key);

		foreach(array_keys($profileList) as $name){
			$profileList[$name] = $this->getProfile($name);
		}

		return $profileList;
	}

	public function useProfile(string $name): bool
	{
		$profile = $this->getProfile($name);

		if($profile){
			$this->setProfile($profile);
			return true;
		}else{
			return false;
		}
	}

	public function setProfile(DockerProfile $profile): void
	{
		$command = ['docker'];

		$command[] = "-H=".$profile->getHost().":".$profile->getPort();

		if($profile->hasTLS()){
			$command[] = '--tlsverify';
			$command[] = "--tlscacert=".$profile->getTLScacert();
			$command[] = "--tlscert=".$profile->getTLScert();
			$command[] = "--tlskey=".$profile->getTLSkey();
		}

		$this->profile = $profile->getName();
		$this->command = implode(" ", $command);
	}

	public function exec(string $subcommand): array
	{
		return Shell::exec("$this->command $subcommand");
	}

	public function passthru(string $subcommand): int
	{
		return Shell::passthru("$this->command $subcommand");
	}
}
