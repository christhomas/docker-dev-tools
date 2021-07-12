<?php declare(strict_types=1);

namespace DDT\Tool;

use DDT\CLI;
use DDT\Config\SystemConfig;
use DDT\Exceptions\Tool\CommandNotFoundException;

abstract class Tool
{
	/** @var string  */
	protected $name;

	/** @var CLI  */
    protected $cli;

    public function __construct(string $name, CLI $cli)
	{
		$this->name = $name;
		$this->cli = $cli;
	}

    public function handle(): void
    {
        $argList = $this->cli->getArgList();

        if(count($argList) === 0){
            $this->help();
        }

        foreach($argList as $arg){
            $command	= strtolower($arg['name']);
            $command	= str_replace(['-','_'], ' ', $command);
            $command	= ucwords($command);
            $command	= str_replace(' ', '', $command);
            $method		= lcfirst($command);

            if(!method_exists($this, $method)){
                throw new CommandNotFoundException("ddt $this->name", $command);
            }

            call_user_func([$this, $method]);
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    abstract public function getTitle(): string;
    abstract public function getDescription(): string;
    abstract public function getShortDescription(): string;
    
    public function getExamples(): string
    {
        return '';
    }

    public function getOptions(): string
    {
        return '';
    }

    public function getNotes(): string
    {
        return '';
    }

    public function getHelp(): string
    {
        $section = [];

        $title = $this->getTitle();
        if(!empty($title)){
            $section[] = $title;
        }
        
        $examples = $this->getExamples();
        if(!empty($examples)){
            $section[] = "{blu}Examples:{end}\n$examples";
        }

        $description = $this->getDescription();
        if(!empty($description)){
            $section[] = "{blu}Description:{end}\n$description";
        }

        $options = $this->getOptions();
        if(!empty($options)){
            $section[] = "{blu}Options:{end}\n$options";
        }
        
        return implode("\n\n", $section);
    }

    protected function help(): void
    {
        \Script::die($this->getHelp());
    }

    static public function list(): array
    {
        return array_map(function($t){ 
            return ['name' => str_replace(['tool', '.php'], '', strtolower(basename($t))), 'path' => $t];
        }, glob(__DIR__ . "/?*Tool.php"));
    }

    static public function instance(string $name, CLI $cli, SystemConfig $systemConfig): Tool
    {
        $class = 'DDT\\Tool\\'.ucwords($name).'Tool';

        if(class_exists($class) === false){
            throw new \DDT\Exceptions\Tool\ToolNotFoundException($name);
        }

        return new $class($cli, $systemConfig);
    }
}
