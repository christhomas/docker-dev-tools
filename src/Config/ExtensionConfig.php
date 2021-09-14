<?php declare(strict_types=1);

namespace DDT\Config;

use DDT\Exceptions\Config\ConfigWrongTypeException;

class ExtensionConfig extends BaseConfig
{
    private $key = 'extensions';

    /** @var SystemConfig */
    private $config;

    public function __construct(SystemConfig $config)
    {
        $this->config = $config;

        if($this->config->getKey($this->key) === null){
			$this->config->setKey($this->key, []);
		}
    }

    public function getDefaultFilename(): string
    {
        return '.ddt-extension.json';
    }

    public function add(string $name, string $url, string $path): bool
    {
        $extensions = $this->config->getKey($this->key);
        $extensions[$name] = ['url' => $url, 'path' => $path];

        return $this->config->write();
    }

    public function remove(string $name): bool
    {
        $extensions = $this->config->getKey($this->key);
        unset($extensions[$name]);

        return $this->config->write();
    }

    public function list(): array
    {
        return $this->config->getKey($this->key);
    }
}