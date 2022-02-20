<?php declare(strict_types=1);

namespace DDT\Tool;

use DDT\CLI;

class ComposerTool extends Tool
{
    public function __construct(CLI $cli)
    {
    	parent::__construct('composer', $cli);
        $this->setToolCommand('__call', null, true);
    }
    
    public function getToolMetadata(): array
    {
        return [
            'title' => 'A totally useless composer wrapper',
            'short_description' => 'A docker wrapper for composer for people who cannot install dependencies because of insufficient php version',
            'description' => implode("\n", [
                'The only reason this tool exists is because some people do not have the required PHP version install on their',
                'computer and it cannot or they do not want to upgrade this php for some reason. This leaves a big problem when',
                'wanting to work with projects that require higher levels of PHP, installing packages will not work or running',
                'composer scripts.',
                '',
                'However, this tool uses docker to augment the users local system so composer will run in a container, with a slight',
                'performance overhead, but with the benefit of being able to run composer without local computer or user restrictions.',
            ]),
            'options' => [
                'This tool provides a passthrough-like interface to composer, whatever the user puts into the command line, is passed to composer',
                'As such, all composer functionality applies here, run composer -h for information on what is available',
            ]
        ];
    }

    public function __call($name, $input)
    {
        $args = array_reduce($input[0], function($a, $i){
            $i = array_merge(['value' => ''], $i);
            $a[] = rtrim("{$i['name']}={$i['value']}", '=');
            return $a;
        }, []);

        $params = implode(' ', $args);
        
        try{
            return $this->cli->passthru("$name $params");   
        }catch(\Exception $e){
            // NOTE: what should I do here?
        }
    }
}