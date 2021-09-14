<?php declare(strict_types=1);

namespace DDT\Extension;

use DDT\Config\ExtensionConfig;

class ExtensionManager
{
    /** @var ExtensionConfig */
    private $config;

    public function __construct(ExtensionConfig $config)
    {
        $this->config = $config;
    }

    public function list(): array
    {
        return $this->config->list();
    }
}