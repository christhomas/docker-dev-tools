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

	public function isLinux(): bool
	{
		if($this->cli->isCommand('uname') === false){
			return false;
		}

		$distro = $this->cli->exec('uname -o', true);

		if(in_array($distro, ['GNU/Linux', 'Linux'])){
			return true;
		}

		return false;
	}

	public function isUbuntu(string $version): bool
	{
		if($this->cli->isCommand('lsb_release') === false){
			return false;
		}

		$lsb_output = $this->cli->exec('lsb_release -d', true);
		list($ignore, $release) = explode(':', $lsb_output);
		$release = trim($release);

		return strpos($release, $version) !== false;
	}
}