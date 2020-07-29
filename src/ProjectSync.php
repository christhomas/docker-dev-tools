<?php
class ProjectSync
{
    private $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function listHook(string $name, array $tokens = []): array
    {
        $list = $this->config->getKey("project_sync.hooks.$name");

        foreach($list as $i => $s){
            foreach($tokens as $t => $r){
                $s = str_replace("{".$t."}", $r, $s);
            }

            if(preg_match("/({file}((?:.|\n)*?){\/file})/", $s, $matches) !== false){
                if(!empty($matches)){
                    if(!file_exists($matches[2])){
                        $s = null;
                    }else{
                        $s = str_replace($matches[1], $matches[2], $s);
                    }
                }
            }

            $list[$i] = $s;
        }

        return array_filter($list);
    }

    public function addHook(string $name, string $script): bool
    {
        $hook = $this->config->getKey("project_sync.hooks.$name");
        $hook[] = $script;

        $this->config->setKey("project_sync.hooks.$name", array_unique(array_values($hook)));
        return $this->config->write();
    }

    public function removeHook(string $name, int $index): bool
    {
        $hook = $this->config->getKey("project_sync.hooks.$name");
        unset($hook[$index]);

        $this->config->setKey("project_sync.hooks.$name", array_unique(array_values($hook)));
        return $this->config->write();
    }
}