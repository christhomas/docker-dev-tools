<?php declare(strict_types=1);

namespace DDT\Tool;

use DDT\Autowire;
use DDT\CLI;
use DDT\Exceptions\Tool\ToolNotFoundException;

class EntrypointTool extends Tool
{
    public function __construct(CLI $cli)
    {
        parent::__construct('entrypoint', $cli);

        $this->setDebug((bool)$cli->getArg('--debug', false, true));
        $this->setQuiet((bool)$cli->getArg('--quiet', false, true));
    }

    public function setDebug(bool $enable): void
    {
        $this->debug = $enable;
        $this->cli->enableErrors($enable);
        $this->cli->listenChannel('debug', $enable);
        
        if($enable){
            $state = $enable ? 'enabled' : 'disabled';
            $this->cli->print("{yel}[SYSTEM]:{end} Errors $state\n");
        }
    }

    public function getDebug(): bool
    {
        return $this->debug;
    }

    public function setQuiet(bool $enable): void
    {
        $this->quiet = $enable;
        $this->cli->listenChannel('quiet', $enable);

        if($enable){
            $this->cli->print("{yel}[SYSTEM]:{end} Quiet output enabled\n");
        }
    }

    public function getQuiet(): bool 
    {
        return $this->quiet;
    }

    public function handle()
    {        
        $arg = $this->cli->shiftArg();
        $tool = null;

        if(!empty($arg)){
            $name = strtolower($arg['name']);
            
            // We check if the tool requested is the entrypoint, which would be weird
            // But we block this stupid thing from happening anyway
            if($name !== $this->name) {
                $tool = $this->getTool($name);
            }
        }

        if($tool instanceof Tool){                       
            if($this->cli->countArgs() === 0){
                // There were no commands or arguments, show help
                $response = $tool->help();
            }else if($method = $tool->getToolDefaultCommand()){
                // There is a default command, call it with all the args passed
                $autowire = container(Autowire::class);
                $response = $autowire->callMethod($tool, $method, $this->cli->getArgList());
            }else{
                $method = $this->cli->shiftArg();
                $method = $tool->getToolCommand($method['name']);

                $autowire = container(Autowire::class);
                $response = $autowire->callMethod($tool, $method, $this->cli->getArgList());
            }
    
            if(is_string($response)){
                $response = $this->cli->print($response."\n");
            }
    
            return $response;
        }

        return $this->cli->print($this->help());
    }

    public function getToolMetadata(): array
    {
        $list = array_map(function($t){ 
            return ['name' => str_replace(['tool', '.php'], '', strtolower(basename($t))), 'path' => $t];
        }, glob(__DIR__ . "/../Tool/?*Tool.php"));

        $options = [];

        foreach($list as $tool){
            // Don't process 'itself' or 'entrypoint'
            if($tool['name'] === $this->name){
                continue;
            }

            /** @var Tool */
            $instance = $this->getTool($tool['name']);

            $metadata = $instance->getToolMetadata();
            $shortDescription = array_key_exists('short_description', $metadata) ? $metadata['short_description'] : $metadata['description'];
            
            $options[] = "  {yel}{$instance->getToolName()}{end}: {$shortDescription}";
        }

        return [
            'title' => 'Main Help',
            'description' => trim(
                "The docker dev tools provides multiple tools which will assist you when building a reliable, \n". 
                "stable development environment. See the below options for subcommands that you can run for \n". 
                "specific functionality which also provide their own help when run without arguments"
            ),
            'options' => implode("\n", $options),
        ];
    }
}