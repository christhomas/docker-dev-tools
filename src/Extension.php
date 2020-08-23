<?php
class Extension{
	public function __construct(Config $config)
	{
		$this->config = $config;
	}

	public function install(string $name, string $url): bool
	{
		$path = CLI::getToolPath("/extensions/$name");

		$git = new Git($url);
		$git->clone($path);

		$pathConfig = new PathConfig($this->config);
		$pathConfig->add($path);

		return $this->config->addExtension($name, $url, $path);
	}

	public function uninstall(string $name): bool
	{
		$path = CLI::getToolPath("/extensions/$name");

		$pathConfig = new PathConfig($this->config);
		$pathConfig->remove($path);

		// NOTE: BE VERY CAREFUL HERE!
		if($path == "/" || strpos($path, ".") === 0 || strpos($path, "extensions") === false){
			throw new Exception("Refusing to work with this path, it's dangerous");
		}

		if(!is_dir($path)){
			throw new DirectoryMissingException("This path '$path' does not exist");
		}

		$cmd = "rm -rf $path";
		Text::print("{red}WARNING: BE CAREFUL THE PATH IS CORRECT{end}\n");
		$answer = CLI::ask("Should we remove the extension directory with the command '$cmd'?",["yes"]);

		if($answer === "yes") {
			Shell::passthru($cmd);
		}

		return $this->config->removeExtension($name);
	}

	public function list(): array
	{
		return glob(CLI::getToolPath("/extensions/*"), GLOB_ONLYDIR);
	}
}
