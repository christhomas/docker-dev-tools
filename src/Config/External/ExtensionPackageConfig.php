<?php declare(strict_types=1);

namespace DDT\Config\External;

use DDT\Config\BaseConfig;

class ExtensionPackageConfig extends BaseConfig
{
    public function getDefaultFilename(): string
    {
        return 'ddt-extension.json';
    }

    static public function instance(string $filename, ?bool $readonly=false): self
    {
        return container(self::class, ['filename' => $filename, 'readonly' => $readonly]);
    }

    public function getTest(): string
    {
        return $this->getKey('.test') ?? 'echo no test specified';
    }
}