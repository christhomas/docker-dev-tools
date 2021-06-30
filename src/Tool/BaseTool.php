<?php declare(strict_types=1);

namespace DDT\Tool;

use DDT\CLI;
use DDT\Exceptions\Tool\CommandNotFoundException;

abstract class BaseTool
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

    abstract protected function help(): void;
}
