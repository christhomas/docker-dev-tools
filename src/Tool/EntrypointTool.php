<?php declare(strict_types=1);

namespace DDT\Tool;

use DDT\CLI;
use DDT\Text\Text as Text;
use DDT\Exceptions\Tool\ToolNotFoundException;
use DDT\Exceptions\Tool\ToolNotSpecifiedException;
use DDT\Exceptions\Tool\CommandNotFoundException;
use DDT\Exceptions\Config\ConfigMissingException;

class EntrypointTool extends Tool
{
    /** @var Text */
    private $text;

    public function __construct(CLI $cli, Text $text)
    {
        parent::__construct($cli->getScript(false), $cli);

        $this->text = $text;
    }

    public function isTool(): bool
    {
        return false;
    }

    public function handle()
    {
        try{
            $response = parent::handle();
            
            if(is_string($response)){
                return $this->cli->print($response);
            }

            return $response;
        }catch(ConfigMissingException $e){
            $this->cli->failure($this->text->box($e->getMessage(), "wht", "red"));
        }catch(ToolNotFoundException $e){
            $this->cli->failure($e->getMessage());
        }catch(ToolNotSpecifiedException $e){
            $this->cli->failure($e->getMessage());
        }catch(CommandNotFoundException $e){
            $this->cli->failure($e->getMessage());
        }
    }

    public function handleArg(array $arg): void
    {
        switch(true){
            case $arg['name'] === '--debug':
                $this->cli->print("{yel}[SYSTEM]:{end} Errors enabled\n");
                $this->cli->enableErrors(true);
                $this->cli->listenChannel('debug');
                break;
            
            case $arg['name'] === '--quiet':
                $this->cli->print("{yel}[SYSTEM]:{end} Quiet output enabled\n");
                $this->cli->listenChannel('quiet', false);
                break;
        }
    }

    public function handleCommand(array $command)
    {
        $tool = $this->createTool($command['name']);
        
        if($tool->isTool()){
            return $tool->handle();
        }
        
        throw new ToolNotFoundException($command['name']);
    }

    public function createTool(string $name)
    {
        try{
            return container('DDT\\Tool\\'.ucwords($name).'Tool');
        }catch(\Exception $e){
            $this->cli->debug("{red}".$e->getMessage()."{end}");
            throw new ToolNotFoundException($name, 0, $e);
        }
    }

    public function getToolMetadata(): array
    {
        $list = array_map(function($t){ 
            return ['name' => str_replace(['tool', '.php'], '', strtolower(basename($t))), 'path' => $t];
        }, glob(__DIR__ . "/../Tool/?*Tool.php"));

        $options = [];

        foreach($list as $tool){
            /** @var Tool */
            $instance = $this->createTool($tool['name']);

            if($instance->isTool()){
                $metadata = $instance->getToolMetadata();
                $shortDescription = array_key_exists('short_description', $metadata) ? $metadata['short_description'] : $metadata['description'];
                
                $options[] = "  {yel}{$instance->getToolName()}{end}: {$shortDescription}";
            }
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