<?php declare(strict_types=1);

namespace DDT\Tool;

use DDT\CLI;
use DDT\Config\SystemConfig;

class ComposerTool extends Tool
{
    public function __construct(CLI $cli)
    {
    	parent::__construct('composer', $cli);
    }

    public function getTitle(): string{ return ''; }
    public function getShortDescription(): string{ return ''; }
    public function getDescription(): string{ return ''; }

    protected function help(): string
    {
        $this->runComposer();

        return '';
    }

    protected function runComposer(?string $params=null)
    {
        try{
            $this->cli->passthru("composer $params");
        }catch(\Exception $e){
            // NOTE: what should I do here?
        }
    }

    public function __call($name, $input)
    {
        $name = preg_replace('/^(.+)Command$/', '$1', $name);

        $args = array_reduce($input, function($a, $i){
            $a[] = rtrim("{$i['name']}={$i['value']}", '=');
            return $a;
        }, []);

        array_unshift($args, $name);
        $params = implode(' ', $args);
        
        $this->runComposer($params);
    }
}