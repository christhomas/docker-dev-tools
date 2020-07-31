<?php
class Ubuntu_16_04
{
    public function __construct()
    {

    }

    public function enableDNS(string $ipAddress): bool
    {
        // need to port old version 16
    }

    public function disableDNS(string $ipAddress): void
    {
        // need to port old version 16
    }

    public function flushDNS(): void
    {
        // for linux we don't have anything to do
    }
}