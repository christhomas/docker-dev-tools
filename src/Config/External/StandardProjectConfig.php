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

	public function getDependencies(?string $script = null): array
	{
		$dependencies = $this->getKey('.dependencies') ?? [];

		foreach($dependencies as $project => $config){
			if(!array_key_exists('scripts', $config)){
				// If a dependency has no predefined config
				// It means it'll accept any script request
				// If the project provides that script name
				// So we just setup a structure here with 
				// predefined data in it

				$dependencies[$project]['scripts'] = [
					$script => $script,
				];
			}else if(array_key_exists($script, $config['scripts'])){
				$value = $config['scripts'][$script];

				if($value === false){
					// script was found, but it's value was false, 
					// this means to block running scripts on this dependency
					// so we have to remove the entire dependency
					unset($dependencies[$project]);
				}else{
					// script exists in this service, filter the others out
					$dependencies[$project]['scripts'] = [
						$script => $value
					];
				}
			}else if($script){
				// script is not null, but not found, remove this entire dependency
				unset($dependencies[$project]);
			}
		}

		return $dependencies;
	}
}