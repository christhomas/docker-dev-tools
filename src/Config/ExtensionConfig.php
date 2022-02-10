<?php declare(strict_types=1);

namespace DDT\Config;

use DDT\Exceptions\Config\ConfigWrongTypeException;

class ExtensionConfig
{
    private $key = 'extensions';

    /** @var SystemConfig $config */
	private $config;

	public function __construct(SystemConfig $config)
	{
        $this->config = $config;

        // NOTE: Don't use list() here, we must ensure it's never null in the first place
        if($this->config->getKey($this->key) === null){
			$this->config->setKey($this->key, []);
		}
    }

    public function add(string $name, string $url, string $path): bool
    {
        $extensions = $this->list();
        $extensions[$name] = ['url' => $url, 'path' => $path];
        
        $this->config->setKey($this->key, $extensions);

        return $this->config->write();
    }

    public function remove(string $name): bool
    {
        $extensions = $this->list();
        unset($extensions[$name]);

        return $this->config->write();
    }

    public function list(): array
    {
        return $this->config->getKey($this->key);
    }
}