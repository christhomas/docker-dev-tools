<?php declare(strict_types=1);

namespace DDT\Config\External;

use DDT\Config\BaseConfig;

class ExtensionPackageConfig extends BaseConfig
{
    public function getDefaultFilename(): string
    {
        return 'ddt-extension.json';
    }
}