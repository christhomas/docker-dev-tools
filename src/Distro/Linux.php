<?php declare(strict_types=1);

namespace DDT\Distro;

class Linux implements DistroInterface
{
	public function createIpAddressAlias(string $ipAddress): bool
	{
		try{
			if(!empty($ipAddress)){
                \Shell::exec("sudo ip addr add $ipAddress/24 dev lo label lo:40");
                return true;
            }
		}catch(\Exception $e){ }

        return false;
	}

	public function removeIpAddressAlias(string $ipAddress): bool
	{
		try{
			if(in_array($ipAddress, ['127.001', '127.0.0.1'])){
				return false;
			}

			if(!empty($ipAddress)){
				\Shell::exec("sudo ip addr del $ipAddress/24 dev lo");
				return true;
			}
		}catch(\Exception $e){ }

		return false;
	}

    public function enableDNS(): bool
    {
        throw new \UnsupportedDistroException($this->distroName);
    }

    public function disableDNS(): bool
    {
        throw new \UnsupportedDistroException($this->distroName);
    }

    public function flushDNS(): void
    {
        throw new \UnsupportedDistroException($this->distroName);
    }
}