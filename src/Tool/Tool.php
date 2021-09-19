<?php declare(strict_types=1);

namespace DDT\Tool;

use DDT\Autowire;
use DDT\CLI;
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

    public function __construct(string $name, CLI $cli)
	{
		$this->name = $name;
		$this->cli = $cli;
        $this->entrypoint = $this->cli->getScript(false);
	}

    public function isTool(): bool
    {
        return true;
    }

    public function getName(): string
    {
        return $this->name;
    }

    protected function getCommandMethod(array $command)
    {
        if(empty($command) || !array_key_exists('name', $command) || empty($command['name'])){
            throw new CommandInvalidException();
        }

        $name = strtolower($command['name']);

        $command = $name;
        $command = str_replace(['-', '_'], ' ', $command);
        $command = ucwords($command);
        $command = str_replace(' ', '', $command);
        $command = lcfirst($command);
        $command = $command . "Command";

        if(!is_callable([$this, $command])){
            throw new CommandNotFoundException("$this->entrypoint $this->name", $name);
        }

        return $command;
    }

    /**
     * Handle the command line parameters
     * 
     * This is generic since it delegates it's work to handleArg/handleCommand to do the specifics
     */
    public function handle()
    {
        while($this->cli->countArgs() > 0){
            $arg = $this->cli->shiftArg();

            if(strpos($arg['name'], '--') === 0){
                $this->handleArg($arg);
            }else{
                return $this->handleCommand($arg);
            }
        }

        return $this->help();
    }

    /**
     * Handle what to do with the current argument being processed
     * 
     * The tool must overload this method with it's own custom implementation based on how it processed arguments
     * into a meaningful way inside the tool
     */
    public function handleArg(array $arg): void
    {
        $this->cli->debug(get_class($this). " - Unhandled argument named '{$arg['name']}' with value '{$arg['value']}'");
    }
    
    /**
     * Handle what to do with the current command being processed
     * 
     * All tools do the same thing, they search for a method called cmdCommand where cmd is what was on the command line
     * and if found, it's executed and
     */
    public function handleCommand(array $command)
    {
        $command = $this->getCommandMethod($command);

        $args = $this->cli->getArgList();

        $autowire = new Autowire(container());
        return $autowire->callMethod($this, $command, $args);
    }

    // The next three methods are required for basic help functionality, they can't be provided generically
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
}
