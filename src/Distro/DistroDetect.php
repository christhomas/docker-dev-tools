<?php declare(strict_types=1);

namespace DDT\Distro;

class DistroDetect
{
    static public function get(): DistroInterface
    {
        if(ucwords(strtolower(PHP_OS)) === 'Darwin'){
			return new Darwin();
		}

		if(\Shell::isCommand('lsb_release')){
			$lsb_output = \Shell::exec("lsb_release -d", true);
			list($ignore, $linuxDistro) = explode(":",$lsb_output);
			$linuxDistro = trim($linuxDistro);

			$distroMap = [
				"16.04" => DDT\Distro\Ubuntu_16_04::class,
				"16.10" => DDT\Distro\Ubuntu_16_04::class,
				"18.04" => DDT\Distro\Ubuntu_18_04::class,
				"18.10" => DDT\Distro\Ubuntu_18_04::class,
			];

			foreach($distroMap as $version => $class){
				if(strpos($linuxDistro, $version) !== false){
					return new $class();
				}
			}
		}

		throw new \UnsupportedDistroException($linuxDistro);
    }
}