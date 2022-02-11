<?php declare(strict_types=1);

namespace DDT\Tool;

use DDT\CLI;
use DDT\Config\SystemConfig;
use DDT\Exceptions\Config\ConfigMissingException;
use DDT\Text\Text;

class ConfigTool extends Tool
{
	/** @var Text */
	private $text;

    public function __construct(CLI $cli, Text $text)
    {
    	parent::__construct('config', $cli);

		$this->text = $text;
    }

	public function getToolMetadata(): array
	{
		$entrypoint = $this->entrypoint . " " . $this->getToolName();

		return [
			'title' => 'Configuration',
			'description' => '  This tool will manipulate the configuration or query part of it for use in other tools',
			'options' => implode("\n",[
				"  filename: Returns a single string containing the filename",
				"  exists: Script will exit with {yel}code 0{end} if configuration file exists or {yel}code 1{end} if it's missing",
				"  reset: Will reset your configuration file to the default 'empty' configuration, {red}it will destroy any setup you already have{end}",
				"  get: Will retrieve a specific key, if no key is specified, the entire config is shown",
				"  validate: Only validate the file can be read without errors",
				"  version: Output some information about the configuration that is deemed useful",
				"  help: This information, also if no sub command is given help is automatically shown",
			]),
			'examples' => trim(
				"Basic commands are simple to understand:\n".
				"  - {$entrypoint} filename (will output where the system configuration file is located)\n".
				"  - {$entrypoint} version (will output version information, etc)\n".
				"\n".
				"To query parts of the configuration:\n".
				"  - {$entrypoint} get (with no specific key mentioned, will output entire configuration)\n".
				"  - {$entrypoint} get=.type\n".
				"  - {$entrypoint} get=.this.0.must.be.3.valid\n".
				"\n".	
				"The last one will do a recursive lookup drilling down each level that are split by the dots\n".
				"  - key(this) -> index(0) -> key(must) -> key(be) -> index(3) -> key(valid)\n".
				"\n".
				"The json for the above example could be:\n".
				"{cyn}{\n".
				"  \"this\": [\n".
				"    {\n".
				"      \"must\": {\n".
				"        \"be\": [\n".
				"          \"not this\",\n".
				"          \"or this\",\n".
				"          \"neither this\",\n".
				"          {\n".
				"            \"valid\": \"this one! this is index 3\",\n".
				"            \"json\": \"doesn't care if you mix strings with objects or sub-arrays\"\n".
				"          },\n".
				"          \"ignore this\"\n".
				"        ]\n".
				"      }\n".   
				"    }\n".
				"  ]\n".
				"}\n".
				"{end}\n".
				"bash# {$entrypoint} get=.this.0.must.be.3.valid\n".
				"\"this one! this is index 3\"\n"
			),
			'notes' => implode("\n",[
				"All keys begin with '.' (dot), e.g: '.description'",
				"Keys are a dotted syntax that allows you to pluck out a segment of the configuration",
				"If you ask for an invalid heirarchy. This function will return null",
			]),
		];
	}

	public function filename(): string
	{
		$config = SystemConfig::instance();

		return $config->getFilename();
	}

	private function writeNewConfig(): bool
	{
		$newConfig = new SystemConfig(__DIR__ . '/../../default.ddt-system.json');
		return $newConfig->write(getenv('HOME').'/.ddt-system.json');
	}

	/**
	 * I don't think this function does anything useful
	 */
	public function existsCommand(): string
	{
		return is_file($this->filename()) ? 'true' : 'false';
	}

	public function resetCommand(): string
	{
		try{
			// Test if system configuration exists, if yes, it'll pass under the catch and continue as normal
			$this->existsCommand();
		}catch(ConfigMissingException $e){
			// Nope! We need to create the first one, this should only really happen once
			if($this->writeNewConfig()){
				return $this->text->box("The file '\$HOME/.ddt-system.json' file was not found, a new one was written", "blk", "grn");
			}else{
				return $this->text->box("The file '\$HOME/.ddt-system.json' file was not found, but writing a new one has failed for unknown reasons", "wht", "red");
			}
		}

		$reply = $this->cli->ask('Are you sure you want to reset your configuration?', ['yes', 'no']);

		if($reply !== 'yes'){
			return $this->text->box("The request to reset was refused", "wht", "red");
		}

		if($this->writeNewConfig()){
			return $this->text->box("The file '\$HOME/.ddt-system.json' was overwritten with a default template", "blk", "grn");
		}

		return $this->text->box("The file '\$HOME/.ddt-system.json' could not be written, the state of the file is unknown, please manually check it", "wht", "red");
	}

	public function getCommand(?string $key='.', ?bool $raw=null): string
	{
		$config = SystemConfig::instance();
		$value = $config->getKeyAsJson($key);

		return $value . "\n";
	}

	public function deleteCommand(string $key): void
	{
		$config = SystemConfig::instance();
		$config->deleteKey($key);
		$config->write();
	}

	public function setCommand(string $key, string $value): void
	{
		$value = json_decode($value, true);

		if(!empty($value)){
			$config = SystemConfig::instance();
			$config->setKey($key, $value);
			$config->write();
		}else{
			$this->cli->debug("Attempting to set an empty config key '$key'");
		}
	}

	public function validateCommand(): string
	{
		$config = SystemConfig::instance();

		// TODO: the reason this is imploding an array with a string string
		// TODO: is because it should be validating other things too
		return implode("\n", [
			// FIXME: add extensions to this output
			// FIXME: add projects to this output
			$this->text->box("The system configuration in file '{$config->getFilename()}' was valid", 'blk', 'grn'),
		]);
	}

	public function versionCommand(): string
	{
		$config = SystemConfig::instance();

		return $config->getVersion() . "\n";
	}
}
