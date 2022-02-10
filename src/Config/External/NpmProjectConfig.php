<?php declare(strict_types=1);

namespace DDT\Config\External;

class NpmProjectConfig extends StandardProjectConfig
{
	protected function initDataStore(): void
	{
		$this->setKey('.', $this->getKey('ddt-tools') ?? []);
	}

    public function getDefaultFilename(): string
    {
        return 'package.json';
    }
}