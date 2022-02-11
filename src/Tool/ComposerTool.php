<?php declare(strict_types=1);

namespace DDT\Tool;

use DDT\CLI;

class ComposerTool extends Tool
{
    public function __construct(CLI $cli)
    {
    	parent::__construct('composer', $cli);
    }
    
    public function getToolMetadata(): array
    {
        return [
            'title' => 'A totally useless composer handler',
            'short_description' => 'A docker wrapper for composer for people who cannot install dependencies because of insufficient php version',
        ];
    }

    public function help(): string
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