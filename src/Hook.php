<?php declare(strict_types=1);
class Hook
{
    const BEFORE_INSTALL = "before_install";
    const AFTER_INSTALL = "after_install";
    
    const BEFORE_PULL = "before_pull";
    const AFTER_PULL = "after_pull";
    
    const BEFORE_UNINSTALL = "before_uninstall";
    const AFTER_UNINSTALL = "after_uninstall";

    private $config;
    private $hooks;

    public function __construct(\DDT\Config\ConfigInterface $config)
    {
        $this->config = $config;
        $this->hooks = $this->config->getKey('hooks');
    }

    public function run(string $name): bool
    {
        if(array_key_exists($name, $this->hooks)){
            $list = $this->hooks[$name];
            var_dump($list);
            return true;
        }

        return false;
    }

    public function getResults(): array
    {
        return [];
    }
}