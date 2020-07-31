<?php
class Network
{
    private $distroName;
    private $distro;

	public function __construct()
	{
	    list($ignore,$distro) = explode(":",Shell::exec("lsb_release -d", true));
	    $this->distroName = trim($distro);

        $this->distro = null;

        switch(true){
			case strpos($this->distroName, "16.04") !== false:
				$this->distro = new Ubuntu_16_04();
				break;

            case strpos($this->distroName, "18.10") !== false:
                $this->distro = new Ubuntu_18_10();
                break;
        }
	}

	public function installIPAddress(string $ipAddress): bool
	{
		try{
			if(!empty($ipAddress)){
                Shell::exec("sudo ip addr add $ipAddress/24 dev lo label lo:40");
                return true;
            }
		}catch(Exception $e){ }

        return false;
	}

	public function uninstallIPAddress(string $ipAddress): bool
	{
		try{
			if(in_array($ipAddress, ['127.001', '127.0.0.1'])){
				return false;
			}

			if(!empty($ipAddress)){
				Shell::exec("sudo ip addr del $ipAddress/24 dev lo");
				return true;
			}
		}catch(Exception $e){ }

		return false;
	}

    public function enableDNS(): bool
    {
        if(!$this->distro) {
            throw new UnsupportedDistroException($this->distroName);
        }

        return $this->distro->enableDNS();
    }

    public function disableDNS(): bool
    {
        if(!$this->distro) {
            throw new UnsupportedDistroException($this->distroName);
        }

        return $this->distro->disableDNS();
    }

    public function flushDNS(): void
    {
        if(!$this->distro) {
            throw new UnsupportedDistroException($this->distroName);
        }

        $this->distro->flushDNS();
    }
}
