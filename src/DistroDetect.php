<?php declare(strict_types=1);

namespace DDT;

class DistroDetect
{
	static public function isDarwin(): bool
	{
		return strtolower(PHP_OS) === 'darwin';
	}

	static public function isUbuntu(string $version): bool
	{
		if(\Shell::isCommand('lsb_release')){
			$lsb_output = \Shell::exec("lsb_release -d", true);
			list($ignore, $release) = explode(":", $lsb_output);
			$release = trim($release);

			return strpos($release, $version) !== false;
		}

		throw new \UnsupportedDistroException('unknown linux distribution');
	}
}