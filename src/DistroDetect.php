<?php declare(strict_types=1);

namespace DDT;

class DistroDetect
{
	/** @var CLI */
	private $cli;

	public function __construct(CLI $cli)
	{
		$this->cli = $cli;
	}

	public function isDarwin(): bool
	{
		return strtolower(PHP_OS) === 'darwin';
	}

	public function isUbuntu(string $version): bool
	{
		if($this->cli->isCommand('lsb_release')){
			$lsb_output = $this->cli->exec("lsb_release -d", true);
			list($ignore, $release) = explode(":", $lsb_output);
			$release = trim($release);

			return strpos($release, $version) !== false;
		}

		throw new \UnsupportedDistroException('unknown linux distribution');
	}
}