<?php declare(strict_types=1);

class DockerProfile implements JsonSerializable
{
	protected $name;
	protected $host;
	protected $port;
	protected $tlscacert;
	protected $tlscert;
	protected $tlskey;

	public function __construct(string $name, string $host, int $port, ?string $tlscacert=null, ?string $tlscert=null, ?string $tlskey=null)
	{
		if(empty($name)) throw new Exception('name param cannot be empty');
		if(empty($host)) throw new Exception('host param cannot be empty');
		if($port < 0) throw new Exception('port param must be a positive integer');

		$this->name = $name;

		$this->host = $host;
		$this->port = $port;

		$this->tlscacert = $tlscacert;
		$this->tlscert = $tlscert;
		$this->tlskey = $tlskey;

		// Check if they're ok, if not, unset them back to null
		if($this->hasTLS() === false){
			$this->tlscacert = null;
			$this->tlscert = null;
			$this->tlskey = null;
		}
	}


	public function getName(): string
	{
		return $this->name;
	}

	public function getHost(): string
	{
		return $this->host;
	}

	public function getPort(): int
	{
		return $this->port;
	}

	public function hasTLS(): bool
	{
		$count = count(array_filter([$this->tlscacert, $this->tlscert, $this->tlskey], function($v){
			return $v !== null && file_exists($v);
		}));

		return $count === 3;
	}

	public function getTLScacert(): ?string
	{
		return $this->tlscacert;
	}

	public function getTLScert(): ?string
	{
		return $this->tlscert;
	}

	public function getTLSkey(): ?string
	{
		return $this->tlskey;
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
			"name"		=> $this->name,
			"host"		=> $this->host,
			"port"		=> $this->port,
			"tlscacert"	=> $this->tlscacert,
			"tlscert"	=> $this->tlscert,
			"tlskey"	=> $this->tlskey,
		];
	}
}
