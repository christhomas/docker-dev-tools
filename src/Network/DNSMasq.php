<?php declare(strict_types=1);

namespace DDT\Network;

class DNSMasq
{
    private $network;

    public function __construct(Network $network)
    {
        $this->network = $network;
    }

    public function isRunning(): bool
    {
        // FIXME: should actually return whether it's running or not
        return false;
    }

    public function start()
    {
        // TODO: Do starting action
        $this->enable();
    }

    public function stop()
    {
        // TODO: Do stopping action
        $this->disable();
    }

    public function enable()
    {
        $this->network->enableDNS();
    }

    public function disable()
    {
        $this->network->disableDNS();
    }

    public function refresh()
    {
        \Shell::sudo();
        $this->network->disableDNS();
        $this->network->enableDNS();
        $this->network->flushDNS();
    }

    private function getContainerId()
    {
        return null;
    }

    public function logs(bool $follow=false)
    {
        $containerId = $this->getContainerId();

		$this->docker->logs($containerId);

		$containerId = $this->getContainerId();

		$this->docker->logsFollow($containerId);
    }
}