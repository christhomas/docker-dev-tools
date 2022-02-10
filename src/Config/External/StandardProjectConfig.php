<?php declare(strict_types=1);

namespace DDT\Config\External;

use DDT\Config\BaseConfig;

class StandardProjectConfig extends BaseConfig
{
	/** @var string The path to the project the config represents */
	private $path;

	public function __construct(string $filename)
	{
		parent::__construct($filename);

		$this->setPath($filename);
		$this->initDataStore();
	}

	public function getDefaultFilename(): string
    {
        return 'ddt-project.json';
    }

	protected function initDataStore(): void
	{
		// do nothing
	}

	public function write(?string $filename=null): bool
	{
		// do nothing
		// these files cannot be saved, but pretend everything is ok ;)
		return true;
	}

	private function setPath(string $filename): void
	{
		$this->path = is_dir($filename) ? $filename : dirname($filename);
	}

	public function getPath(): string
	{
		return $this->path;
	}
}