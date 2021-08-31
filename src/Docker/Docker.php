<?php declare(strict_types=1);

namespace DDT\Docker;

use DDT\CLI;
use DDT\Config\DockerConfig;
use DDT\Exceptions\Docker\DockerInspectException;
use DDT\Exceptions\Docker\DockerMissingException;
use DDT\Exceptions\Docker\DockerNetworkCreateException;
use DDT\Exceptions\Docker\DockerNetworkExistsException;
use DDT\Exceptions\Docker\DockerNetworkNotFoundException;
use DDT\Exceptions\Docker\DockerNotRunningException;

class Docker
{
    /** @var CLI */
    private $cli;

    /** @var DockerConfig */
    private $config;

	private $profile;
	private $version;
    private $command = 'docker';

    const DOCKER_NOT_RUNNING = "The docker daemon is not running";
	const DOCKER_PORT_ALREADY_IN_USE = "Something is already using port '{port}' on this machine, please stop that service and try again";
	const DOCKER_NETWORK_ALREADY_ATTACHED = "/endpoint with name (?<container>[^\s].*) already exists in network (?<network>[^\s].*)/";

	private function parseErrors(string $message, array $tokens = []): string
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

	private function isError(string $message, string $pattern): bool
	{
		return !!preg_match($pattern, $message, $matches);
	}

    public function __construct(CLI $cli, DockerConfig $config)
	{
        $this->cli = $cli;

        $this->setConfig($config);

        // FIXME: how to di-inject this? 
        $this->setProfile(new DockerRunProfile('default'));

        if($this->cli->isCommand($this->command) === false){
            throw new DockerMissingException();
        }

        $this->version = $this->getVersion();

        if(!$this->isRunning()){
            throw new DockerNotRunningException();
        }
    }

	public function getVersion(): array
    {
        return json_decode($this->command('version --format "{{json .}}"'), true);
    }

	public function isRunning(): bool
	{
	    return is_array($this->version) && array_key_exists('Server', $this->version) && !empty($this->version['Server']);
	}

    public function setConfig(DockerConfig $config): void
    {
        $this->config = $config;
    }

    public function getConfig(): DockerConfig
    {
        return $this->config;
    }

	public function listProfile(): array
	{
		return $this->config->listProfile();
	}

    public function setProfile(DockerRunProfile $profile): void
    {
        $this->profile = $profile;
    }

    public function pull(string $image): int
    {
        try{
			return $this->cli->passthru("$this->command pull $image");
		}catch(\Exception $e){
			$this->cli->print("{red}" . $this->parseErrors($e->getMessage()) . "{end}");
		}

		return 1;
    }

	public function command(string $command): string
	{
		$command = implode(' ', array_filter([$this->command, $this->profile->getDockerOptions(), $command]));

        return trim(implode("\n", $this->cli->exec($command)));
	}

	public function exec(string $container, string $command, bool $firstLine=false)
	{
		return $this->command("exec -it $container $command");
		//return $this->cli->exec("$this->command exec -it $container $command", $firstLine);
	}

	public function run(string $image, string $name, array $ports = [], array $volumes = [], array $options = []): ?string
	{
		$command = ["$this->command run -d --restart always"];

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
			return implode("\n", $this->cli->exec(implode(" ", $command), true));
		}catch(\Exception $e){
			// FIXME: I don't think this should die here, but return an exception which can be understood by somewhere above in the hierarchy
			$this->cli->failure($this->parseErrors($e->getMessage(), ["{port}" => $ports]));
		}
	}

	public function pruneContainer(): void
	{
		$this->cli->exec("$this->command container prune -f &>/dev/null");
	}

    public function deleteContainer(string $container): bool
	{
		try{
			$this->cli->exec("$this->command kill $container 1>&2");
			$this->cli->exec("$this->command container rm $container 1>&2");

			return true;
		}catch(\Exception $e){
			\Text::print("{red}".$this->parseErrors($e->getMessage())."{end}\n");
			return false;
		}
	}

	public function createNetwork(string $name): string
	{
		try{
			$this->inspect('network', $name);

			// The network already exists, we can't create it again!
			throw new DockerNetworkExistsException($name);
		}catch(DockerInspectException $e){
			// The network does not exist, lets try to create it
		}

		try{
			$r = $this->cli->exec("$this->command network create $name 2>&1");
			
			return $r[0];
		}catch(\Exception $e){
			$this->cli->debug("The docker network '$name' failed to create with error:\n".$e->getMessage());
			throw new DockerNetworkCreateException($name);
		}
	}

	public function networkAttach(string $network, string $containerId)
	{
		try{
			// TODO: how can I detect whether the network already has this container before doing this?
			// TODO: It throws exceptions when this fails for various reasons
			$this->cli->exec("$this->command network connect $network $containerId");

			return true;
		}catch(\Exception $e){
			if($this->isError($e->getMessage(), self::DOCKER_NETWORK_ALREADY_ATTACHED)){
				return true;
			}

			return false;
		}
	}

	public function networkDetach(string $network, string $containerId)
	{
		// TODO: how can I detect whether the network does not have this container, before trying to delete it
		// TODO: It throws exceptions when this fails for various reasons
		$this->cli->exec("$this->command network disconnect $network $containerId");
	}

    /**
     * @param $type
     * @param $name
     * @return array|null
     * @throws ContainerNotRunningException
     */
	public function inspect(string $type, string $name, ?string $filter='-f \'{{json .}}\''): ?array
	{
		try{
			$result = $this->cli->exec("$this->command $type inspect $name $filter");
			$result = implode("\n",$result);

			// attempt to decode the result, it might fail cause some return values are not valid json
			$r = json_decode($result, true);
			// if empty, then assume decoding it failed, revert back to original value
			if(empty($r) && !is_array($r)) $r = $result;
			// if 'r' is scalar, wrap it in an array, so this function has a predictable return value
			if(is_scalar($r)) $r = [$r];

			return $r;
		}catch(\Exception $e){
		    throw new DockerInspectException($type, $name, 0, $e);
		}
	}

	public function logs(string $containerId): int
	{
		return $this->cli->passthru("$this->command logs $containerId");
	}

	public function logsFollow(string $containerId): int
	{
		return $this->cli->passthru("$this->command logs -f $containerId");
	}
}