<?php declare(strict_types=1);

namespace DDT;

use Script;

class Help
{
    static public function show($name)
    {
        \Script::die(file_get_contents($this->config->getToolsPath('/help/dns.txt')));
    }
}