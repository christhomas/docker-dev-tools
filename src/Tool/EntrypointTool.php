<?php declare(strict_types=1);

namespace DDT\Tool;

use DDT\CLI;

class EntrypointTool extends Tool
{
    /** @var callable The response handler for dealing with what the tool replies with */
    protected $responseHandler;

    public function __construct(CLI $cli)
    {
        parent::__construct($cli->getScript(false), $cli);
        $this->setTerminalResponse();
    }

    public function isTool(): bool
    {
        return false;
    }

    public function setTerminalResponse()
    {
        $this->responseHandler = function(string $output): string {
            $output = \Text::write($output);
            print($output."\n");
            return $output;
        };
    }

    public function setReturnResponse()
    {
        $this->responseHandler = function(string $output): string {
            return $output;
        };
    }

    public function handle()
    {
        return call_user_func($this->responseHandler, parent::handle() ?? '');
    }

    public function handleArg(array $arg): void
    {
        switch(true){
            case $arg['name'] === '--debug':
                \Text::print("{yel}** errors enabled{end}\n");
                $this->cli->enableErrors(true);
                \Text::setDebug($arg['value'] ?? 'true');
                \Shell::setDebug(true);
                break;
            
            case $arg['name'] === '--quiet':
                \Text::print("{yel}** quiet output enabled{end}\n");
                \Text::setQuiet(true);
                break;
        }
    }

    public function handleCommand(array $command)
    {
        $tool = $this->createTool($command['name']);
        
        if($tool->isTool()){
            return $tool->handle();
        }
        
        throw new \DDT\Exceptions\Tool\ToolNotFoundException($command['name']);
    }

    public function createTool(string $name)
    {
        try{
            return container('DDT\\Tool\\'.ucwords($name).'Tool');
        }catch(\Exception $e){
            \Text::print("{debug}{red}".$e->getMessage()."{end}\n{/debug}");
            throw new \DDT\Exceptions\Tool\ToolNotFoundException($name, 0, $e);
        }
    }

    public function getTitle(): string
    {
        return 'Main Help';
    }

    public function getDescription(): string
    {
        return trim("
The docker dev tools provides multiple tools which will assist you when building a reliable, stable development environment
See the below options for subcommands that you can run for specific functionality which also provide their own help when run without arguments");
    }

    public function getShortDescription(): string
    {
        return '';
    }

    public function getOptions(): string
    {
        $network = container(\DDT\Docker\DockerNetwork::class, ['name' => 'testing']);
        var_dump($network->listContainers());
        var_dump($network->attach('8d12a6f558557700643da74856d5a19d75e2b30f2a325b53b424af80da6a91af'));
        die("DEAD: ".__METHOD__);

        $list = array_map(function($t){ 
            return ['name' => str_replace(['tool', '.php'], '', strtolower(basename($t))), 'path' => $t];
        }, glob(__DIR__ . "/../Tool/?*Tool.php"));

        $options = [];

        foreach($list as $tool){
            $instance = $this->createTool($tool['name']);

            if($instance->isTool()){
                $options[] = \Text::write("  {$instance->getName()}: {$instance->getShortDescription()}");
            }
        }

        return implode("\n", $options);
    }
}