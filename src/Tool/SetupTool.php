<?php declare(strict_types=1);

namespace DDT\Tool;

use DDT\CLI;
use DDT\Config\SystemConfig;
use DDT\Text\Text;

class SetupTool extends Tool
{
    /** @var Text */
    private $text;

    /** @var SystemConfig  */
    private $config;

    public function __construct(CLI $cli, Text $text, SystemConfig $config)
    {
    	parent::__construct('setup', $cli);

        $this->text = $text;
        $this->config = $config;
    }

    public function getTitle(): string
    {
        return 'Tools Setup';
    }

    public function getShortDescription(): string
    {
        return 'A tool that manages the installation and upgrade of itself';
    }

    public function getDescription(): string
    {
		return "This tool will setup the basic setup for the docker dev tools";
    }

    public function getExamples(): string
    {
        $entrypoint = $this->cli->getScript(false) . " " . $this->getName();

        return implode("\n", [
            "{yel}Usage Example:{end} $entrypoint {yel}--start --restart --stop{end}",
        ]);
    }

    public function getOptions(): string
	{
		return "\t" . implode("\n\t", [
            "install <path>: Install the tools into the path using the either the optional path given with the parameter or defaults to the current directory",
            "uninstall: Uninstall the tools, given the path from the configuration",
            "test: Test whether the tools are installed and can be executed as expected.",
            "set-path <path>: Update where the tools are installed",
		]);
	}

    public function install()
    {
        /*
        $shellPath = new ShellPath($config);
        $shellPath->install($newPath);

        // We override this value because now we've updated the path, we should test
        $cli->setArg('test', true);*/
    }

    public function uninstall()
    {
        /*
        $shellPath = new ShellPath($config);
        $toolPath = $config->getToolsPath();
        $shellPath->uninstall($toolPath);*/
    } 

    public function test()
    {
        /*
        $shellPath = new ShellPath($config);
        $toolPath = $config->getToolsPath();

        if($shellPath->test($toolPath, $cli->getScript(false))){
            $this->cli->print($this->text->box("The path was successfully installed, you might need to open a new terminal to see the effects", "black", "green"));
        }else{
            $this->cli->print($this->text->box("The tool '" . basename($cli->getScript()) . "' could not set the shell path successfully installed. Please report this error", "white", "red"));
            exit(1);
        }*/
    }

    public function setPath()
    {
        /*
        $config->setToolsPath($path);
	    $config->write();*/
    }
}
