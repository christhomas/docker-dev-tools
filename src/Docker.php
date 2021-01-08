<?php declare(strict_types=1);

class Docker
{
	private $config;
	private $command;
	private $profile;
	private $version;
	private $key = "docker";

	const DOCKER_NOT_RUNNING = "The docker daemon is not running";
	const DOCKER_PORT_ALREADY_IN_USE = "Something is already using port '{port}' on this machine, please stop that service and try again";

	private function parseCommonErrors(string $message, array $tokens = []): string
	{
		if(strpos($message, "Got permission denied while trying to connect to the Docker daemon socket") !== false){
			$message = self::DOCKER_NOT_RUNNING;
		}

		if(strpos($message, "address already in use") !== false){
			$message = self::DOCKER_PORT_ALREADY_IN_USE;
		}

		foreach($tokens as $search => $replace){
			if(is_array($replace)){
				$replace = implode(", ", $replace);
			}
			$message = str_replace($search, $replace, $message);
		}

		return $message;
	}

	public function __construct(SystemConfig $config)
	{
        $this->config = $config;
        $this->profile = 'default';
        $this->command = 'docker';

        if(Shell::isCommand($this->command) === false){
            throw new DockerMissingException();
        }

		$this->version = $this->getVersion();

        if(!$this->isRunning()){
            throw new DockerNotRunningException();
        }
    }

	public function getVersion(): array
    {
        return json_decode(Shell::exec("$this->command version --format \"{{json .}}\"", true), true);
    }

	public function isRunning(): bool
	{
	    return is_array($this->version) && array_key_exists('Server', $this->version) && !empty($this->version['Server']);
	}

	public function pull(string $image): int
	{

		try{
			return Shell::passthru("$this->command pull $image");
		}catch(Exception $e){
			Text::print("{red}".$this->parseCommonErrors($e->getMessage())."{end}");
		}

		return 1;
	}

	public function findContainer(string $container): bool
	{
		try{
			$list = Shell::exec("$this->command container ls");

			foreach($list as $line){
				if(strpos($line, $container) !== false){
					return true;
				}
			}
		}catch(Exception $e){
			Text::print("{red}".$this->parseCommonErrors($e->getMessage())."{end}");
		}

		return false;
	}

	public function getContainerId(string $name): string
	{
        //return Shell::exec("$this->command container ls --filter name=$name --format \"{{.ID}}\"", true);
		return Shell::exec("$this->command container ls --all | grep '$name' | awk '{ print $1 }'", true);
	}

	public function deleteContainer(string $container): bool
	{
		try{
			Shell::exec("$this->command kill $container 1>&2");
			Shell::exec("$this->command container rm $container 1>&2");

			return true;
		}catch(Exception $e){
			Text::print("{red}".$this->parseCommonErrors($e->getMessage())."{end}\n");
			return false;
		}
	}

	public function pruneContainer(): void
	{
		Shell::exec("$this->command container prune -f &>/dev/null");
	}

	public function findRunning(string $image): ?string
    {
        return $this->findRunningByImage($image);
    }

	public function findRunningByImage(string $image): ?string
	{
		try{
			/*$output = Shell::exec("$this->command ps --no-trunc");

			array_shift($output);

			foreach($output as $line){
				if(preg_match("/^([^\s]+)\s+([^\s]+)/", $line, $matches)){
					if($image === $matches[2]){
						return $matches[1];
					}
				}
			}*/
            //$container = $this->inspect()
		}catch(Exception $e){
			Script::failure("{red}".$this->parseCommonErrors($e->getMessage())."{end}\n");
			// catch exception, return null
		}

    	return null;
	}

	public function run(string $image, string $name, array $ports = [], array $volumes = [], array $options = []): ?string
	{
		$command = ["$this->command run -d --restart-always"];

		$command = array_merge($command, $options);

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
			Script::failure($this->parseCommonErrors($e->getMessage(), ["{port}" => $ports]));
		}
	}

	public function exec(string $container, string $command, bool $firstLine=false)
	{
		return Shell::exec("$this->command exec -it $container $command", $firstLine);
	}

	public function command(string $subcommand): array
	{
		return Shell::exec("$this->command $subcommand");
	}

	public function passthru(string $subcommand): int
	{
		return Shell::passthru("$this->command $subcommand");
	}

	public function logs(string $container): int
	{
		return Shell::passthru("$this->command logs $container");
	}

	public function logsFollow(string $container): int
	{
		return Shell::passthru("$this->command logs -f $container");
	}

	public function getNetworkId(string $network): ?string
	{
		if(empty($network)) return null;

		try{
			$networkId = Shell::exec("$this->command network inspect $network -f '{{ .Id }}' 2>/dev/null", true);
            return !empty($networkId) ? $networkId : null;
		}catch(Exception $e){
			return null;
		}
	}

	public function createNetwork(string $network)
	{
		$networkId = $this->getNetworkId($network);

		if(empty($networkId)){
			$networkId = Shell::exec("$this->command network create $network", true);
			Text::print("{blu}Create Network:{end} '$network', id: '$networkId'\n");
		}else{
			Text::print("{yel}Network '$network' already exists{end}\n");
		}

		return $networkId;
	}

	public function deleteNetwork(string $network)
	{
		// TODO
	}

    /**
     * @param $type
     * @param $name
     * @return array|null
     * @throws ContainerNotRunningException
     */
	public function inspect($type, $name): ?array
	{
		try{
			$result = Shell::exec("$this->command $type inspect $name -f '{{json .}}'");
			$result = implode("\n",$result);

			return json_decode($result, true);
		}catch(Exception $e){
		    throw new ContainerNotRunningException($name, 0, $e);
		}
	}

	public function connectNetwork($network, $containerId): ?bool
	{
		try{
			$networkData = $this->inspect('network', $network);

			foreach(array_keys($networkData['Containers']) as $id){
				if($id === $containerId) return false;
			}

			Shell::exec("$this->command network connect $network $containerId");

			return true;
		}catch(Exception $e){
			return null;
		}
	}

	public function disconnectNetwork(string $network, string $containerId): ?bool
	{
		try{
			Shell::exec("$this->command network disconnect $network $containerId");
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
}
