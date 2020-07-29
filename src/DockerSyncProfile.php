<?php declare(strict_types=1);

class DockerSyncProfile implements JsonSerializable
{
	protected $name;
	protected $container;
	protected $localDir;
	protected $remoteDir;

	public function __construct(string $name, string $container, string $localDir, string $remoteDir)
	{
		$namePattern = "^[a-z][a-z0-9\-\_]+$";
        $containerPattern = "^[a-z][a-z0-9\-\_]+$";
        $remoteDirPattern = "^\/[a-z0-9\-\_\/\.]+$";

		if(preg_match("/$namePattern/", $name)){
            $this->name = $name;
        }else{
		    throw new Exception("The profile name '$name' must follow the pattern '$namePattern'");
        }

        if(preg_match("/$containerPattern/", $container)){
            $this->container = $container;
        }else{
            throw new Exception("The container name '$container' must follow the pattern '$containerPattern'");
        }

		if(is_dir($localDir)){
            $this->localDir = rtrim($localDir, '/');
        }else{
		    throw new Exception("The local directory '$localDir' did not exist");
        }

        if(preg_match("/$remoteDirPattern/", $remoteDir)) {
            $this->remoteDir = rtrim($remoteDir, '/');
        }else{
            throw new Exception("The remote directory '$remoteDir' must follow the pattern '$remoteDirPattern'");
        }
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getContainer(): string
    {
        return $this->container;
    }

	public function getLocalDir(): string
	{
		return $this->localDir;
	}

	public function getRemoteDir(): string
	{
		return $this->remoteDir;
	}

	public function getRemoteFilename(string $filename): string
	{
		return $this->remoteDir . str_replace($this->localDir, '', $filename);
	}

	public function __toString(): string
	{
		return json_encode($this->get(), JSON_PRETTY_PRINT);
	}

	public function jsonSerialize(): array
	{
		return $this->get();
	}

	public function get(): array
	{
		return [
		    "container"     => $this->container,
			"local_dir"		=> $this->localDir,
			"remote_dir"	=> $this->remoteDir,
		];
	}
}
