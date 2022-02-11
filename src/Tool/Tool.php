<?php declare(strict_types=1);

namespace DDT\Tool;

use DDT\Autowire;
use DDT\CLI;
use DDT\Exceptions\Tool\CommandInvalidException;
use DDT\Exceptions\Tool\CommandNotFoundException;
use DDT\Exceptions\Tool\ToolNotFoundException;

abstract class Tool
{
	/** @var string */
	protected $name;

	/** @var CLI */
    protected $cli;

    /** @var string The main entrypoint from the terminal */
    protected $entrypoint;

    /** @var array The registered commands for this tool */
    private $commands = [];

    /** @var array A list of functions which cannot be used as tool commands */
    private $protectedFunctions = [
        'registerCommand',
        'isTool',
        'getTool',
        'getToolMetaData',
        'getToolName',
    ];

    public function __construct(string $name, CLI $cli)
	{
		$this->name = $name;
		$this->cli = $cli;
        $this->entrypoint = $this->cli->getScript(false);
        
        $this->registerCommand('help');
	}

    public function registerCommand(string $name, ?callable $callable=null, bool $isDefault=false): void
    {
        $this->command[$name] = [
            'callable' => $callable ?? $name,
            'is_default' => $isDefault
        ];
    }

    public function isTool(): bool
    {
        return true;
    }

    public function getTool(string $name): Tool
    {
        try{
            if(empty($name)) throw new \Exception('Tool name cannot be empty');
            
            return container('DDT\\Tool\\'.ucwords($name).'Tool');
        }catch(\Exception $e){
            $this->cli->debug("{red}".$e->getMessage()."{end}");
            throw new ToolNotFoundException($name, 0, $e);
        }
    }

    public function getToolName(): string
    {
        return $this->name;
    }

    public function getToolMetadata(): array
    {
        return [
            'title' => 'A sample Tool Title',
            'description' => 'Please add getToolMetadata to your tool',
            'commands' => [
                'set: this will set some data',
                'get: this will get some data',
                'add: this will add some data',
                'help: this command is available in all tools'
            ],
            'options' => [
                'TODO: I think I should phase this element out'
            ],
            'examples' => [
                'ddt mytool set some-data'
            ],
            'notes' => [
                'This should be an array of notes',
                'Which can help your users understand your tool',
                'Leave an empty array if you do not want notes to be shown',
                'They are formatted as bulletpoints'
            ],
        ];
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

    public function help(): string
    {
        $section = [];
        $metadata = $this->getToolMetadata();

        $title = array_key_exists('title', $metadata) ? $metadata['title'] : null;
        if(!empty($title)){
            $section[] = "{grn}Docker Dev Tools: $title{end}";
        }

        $description = array_key_exists('description', $metadata) ? $metadata['description'] : null;
        if(!empty($description)){
            $section[] = "{blu}Description:{end}\n$description";
        }
        
        $options = array_key_exists('options', $metadata) ? $metadata['options'] : null;
        if(!empty($options)){
            $section[] = "{blu}Options:{end}\n$options";
        }

        $examples = array_key_exists('examples', $metadata) ? $metadata['examples'] : null;
        if(!empty($examples)){
            $section[] = "{blu}Examples:{end}\n$examples";
        }

        $notes = array_key_exists('notes', $metadata) ? $metadata['notes'] : null;
        if(!empty($notes)) {
            $section[] = "{blu}Notes:{end}\n$notes";
        }

        return implode("\n\n", $section) . "\n";
    }
}
