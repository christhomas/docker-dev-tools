<?php declare(strict_types=1);

namespace DDT\Tool;

use DDT\CLI;
use DDT\Config\SystemConfig;
use DDT\Exceptions\Config\ConfigMissingException;
class ConfigTool extends Tool
{
    public function __construct(CLI $cli)
    {
    	parent::__construct('config', $cli);
    }

    public function getTitle(): string
    {
        return 'Configuration';
    }

    public function getShortDescription(): string
    {
        return 'A tool to manage the configuration of the overall system, setting and getting parameters from the main system config';
    }

    public function getDescription(): string
    {
		return "This tool will manipulate the configuration or query part of it for use in other tools";
    }

	public function getOptions(): string
	{
		return "\t" . implode("\n\t", [
			"filename: Returns a single string containing the filename",
			"exists: Script will exit with {yel}code 0{end} if configuration file exists or {yel}code 1{end} if it's missing",
			"reset: Will reset your configuration file to the default 'empty' configuration, {red}it will destroy any setup you already have{end}",
			"get: Will retrieve a specific key, if no key is specified, the entire config is shown",
			"validate: Only validate the file can be read without errors",
			"version: Output some information about the configuration that is deemed useful",
			"help: This information, also if no sub command is given help is automatically shown",
		]);
	}

	public function getExamples(): string
	{
		$entrypoint = $this->entrypoint . " " . $this->getName();

		return<<<EXAMPLES
Basic commands are simple to understand:
	{$entrypoint} filename (will output where the system configuration file is located)
	{$entrypoint} version (will output version information, etc)
	
To query parts of the configuration: 
	{$entrypoint} get (with no specific key mentioned, will output entire configuration)
	{$entrypoint} get=.type
	{$entrypoint} get=.this.0.must.be.3.valid
		
The last one will do a recursive lookup drilling down each level that are split by the dots
	key(this) -> index(0) -> key(must) -> key(be) -> index(3) -> key(valid)
	
The json for the above example could be:
{cyn}{
	"this": [
		{
			"must": {
				"be": [
					"not this",
					"or this",
					"neither this",
					{
						"valid": "this one! this is index 3",
						"json": "doesn't care if you mix strings with objects or sub-arrays"
					},
					"ignore this"
				]
			}   
		}
	]
}
{end}
bash# {$entrypoint} get=.this.0.must.be.3.valid
"this one! this is index 3"
EXAMPLES;
	}

	public function getNotes(): string
	{
		return "\t- " . implode("\n\t- ", [
			"All keys begin with '.' (dot), e.g: '.description'",
			"Keys are a dotted syntax that allows you to pluck out a segment of the configuration",
			"If you ask for an invalid heirarchy. This function will return null",
		]);
	}

	public function filename(): string
	{
		$config = container(\DDT\Config\SystemConfig::class);

		return $config->getFilename();
	}

	/**
	 * I don't think this function does anything useful
	 */
	public function exists(): string
	{
		return is_file($this->filename()) ? 'true' : 'false';
	}

	public function reset(): string
	{
		$reply = \DDT\CLI::ask('Are you sure you want to reset your configuration?', ['yes', 'no']);

		if($reply === 'yes'){
			return \Text::box("The request to reset was refused", "black", "green");
		}else{
			return \Text::box("The request to reset was refused", "white", "red");
		}


		// if(!$cli->hasArg('validate')){
		// 	if($exists === true && $write === false){
		// 		print(Text::write("The configuration file '{yel}$filename{end}' already exists\n"));
		// 		if($cli->hasArg('break-me')) file_put_contents($filename, file_get_contents($filename)."!@#@#^#$!@#");
		// 	}else{
		// 		print(Text::box("Writing the configuration file: $filename", 'black', 'yellow'));
		// 		$config = new \DDT\Config\SystemConfig(DDT\CLI::getToolPath("/defaults.json"));
		// 		$config->write($filename);
		// 	}
		// }
		

		$this->cli->failure("implement: " . __METHOD__);
		/*
		$reset = $cli->hasArg('reset');

		if($exists === true && $reset === true) {
		$reply = \DDT\CLI::ask('Are you sure you want to reset your configuration?', ['yes', 'no']);

		if($reply !== 'yes'){
			exit(0);
		}

		$write = true;
		*/
	}

	public function get($value/*, $arg1, $arg2, $arg3*/): string
	{
		return $this->config->getKeyAsJson($value);
	}

	public function delete($value): string
	{
		$this->cli->failure("implement: " . __METHOD__);
		/*
		if($exists && $removeKey = $cli->getArgWithVal('remove-key')){
			$config = container(\DDT\Config\SystemConfig::class);
			$config->deleteKey($removeKey);
			$config->write();
			exit(0);
		}
		*/
	}

	public function validate(): string
	{
		// FIXME: add extensions to this output
		// FIXME: add projects to this output

		$config = container(\DDT\Config\SystemConfig::class);

		return implode("\n", [
			\Text::box("The system configuration in file '{$config->getFilename()}' was valid", 'black', 'green'),
		]);
	}

	public function version(): string
	{
		$config = container(\DDT\Config\SystemConfig::class);

		return $config->getVersion();
	}
}
