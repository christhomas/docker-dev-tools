<?php declare(strict_types=1);

namespace DDT\Tool;

use DDT\CLI;
use DDT\Config\SystemConfig;
use DDT\Exceptions\Tool\CommandInvalidException;
use DDT\Exceptions\Tool\CommandNotFoundException;

abstract class Tool
{
	/** @var string */
	protected $name;

	/** @var CLI */
    protected $cli;

    /** @var string The main entrypoint from the terminal */
    protected $entrypoint;

    /** @var string The tool being requested */
    protected $command;

    /** @var value The value the command was given, if it was equalled to something */
    protected $value = null;

    /** @var array The arguments passed to the tool */
    protected $args = [];

    /** @var callable The response handler for dealing with what the tool replies with */
    protected $responseHandler;

    /** @var callable The default handler when no arguments were given, default: 'help' function */
    protected $defaultHandler;

    public function __construct(string $name, CLI $cli)
	{
		$this->name = $name;
		$this->cli = $cli;
        $this->setTerminalResponse();
        $this->setDefaultHandler([$this, 'help']);
	}

    protected function setCommand(array $command)
    {
        if(empty($command) || !array_key_exists('name', $command) || empty($command['name'])){
            throw new CommandInvalidException();
        }

        $this->command = strtolower($command['name']);
        $this->command = str_replace(['-','_'], ' ', $this->command);
        $this->command = ucwords($this->command);
        $this->command = str_replace(' ', '', $this->command);
        $this->command = lcfirst($this->command);

        // The value of this command if it was given one
        $this->value = $command['value'];

        $entrypoint = $this->cli->getScript(false);

        if(!method_exists($this, $this->command)){
            throw new CommandNotFoundException("$entrypoint $this->name", $this->command);
        }
    }

    protected function setArgs(array $args)
    {
        $this->args = $args;
    }

    public function handle()
    {
        if($this->cli->countArgs() < 1){
            $response = call_user_func($this->defaultHandler);
        }else{
            $this->setCommand($this->cli->shiftArg());
            $this->setArgs($this->cli->getArgList());

            $response = call_user_func_array([$this, $this->command], array_merge([$this->value], $this->args));
        }

        return call_user_func($this->responseHandler, $response ?? '');
    }

    public function setDefaultHandler(callable $defaultHandler)
    {
        $this->defaultHandler = $defaultHandler;
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

    public function getName(): string
    {
        return $this->name;
    }

    // The next three methods are required for basic help functionality
    abstract public function getTitle(): string;
    abstract public function getDescription(): string;
    abstract public function getShortDescription(): string;
    // It's reasonable that these three methods don't exist in every tool, so we let them return empty by default so they can be safely skipped
    public function getOptions(): string{ return ''; }
    public function getExamples(): string{ return ''; }
    public function getNotes(): string{ return ''; }

    protected function help(): string
    {
        $section = [];

        $title = $this->getTitle();
        if(!empty($title)){
            $section[] = "{grn}Docker Dev Tools: $title{end}";
        }

        $description = $this->getDescription();
        if(!empty($description)){
            $section[] = "{blu}Description:{end}\n$description";
        }
        
        $options = $this->getOptions();
        if(!empty($options)){
            $section[] = "{blu}Options:{end}\n$options";
        }

        $examples = $this->getExamples();
        if(!empty($examples)){
            $section[] = "{blu}Examples:{end}\n$examples";
        }

        $notes = $this->getNotes();
        if(!empty($notes)) {
            $section[] = "{blu}Notes:{end}\n$notes";
        }
        
        return implode("\n\n", $section) . "\n";
    }

    static public function instance(string $name): Tool
    {
        try{
            return container('DDT\\Tool\\'.ucwords($name).'Tool');
        }catch(\Exception $e){
            throw new \DDT\Exceptions\Tool\ToolNotFoundException($name);
        }
    }
}
