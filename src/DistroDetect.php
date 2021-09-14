<?php declare(strict_types=1);

namespace DDT;

use DDT\Exceptions\UnsupportedDistroException;

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
		if($this->cli->isCommand('uname') && $this->cli->exec('uname -o', true) === 'GNU/Linux'){
			return true;
		}

		throw new UnsupportedDistroException('unknown distribution and command uname does not exist');
	}

	public function isUbuntu(string $version): bool
	{
		if($this->cli->isCommand('lsb_release')){
			$lsb_output = $this->cli->exec('lsb_release -d', true);
			list($ignore, $release) = explode(':', $lsb_output);
			$release = trim($release);

			return strpos($release, $version) !== false;
		}

		throw new UnsupportedDistroException('unknown linux distribution');
	}
}