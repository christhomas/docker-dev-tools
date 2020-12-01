<?php
class ExtensionManager{
	public function __construct(SystemConfig $config)
	{
		$this->config = $config;
	}

	public function install(string $name, string $url): bool
	{
		$path = $this->config->getToolsPath("/extensions/$name");

		$git = new Git();
		$git->clone($url, $path);		

		$extensionConfig = new ExtensionConfig($path);
		$hook = new Hook($extensionConfig);
		$hook->run(Hook::BEFORE_INSTALL);

		$shellPath = new ShellPath($this->config);
		$shellPath->add($path);

		if(!$this->config->addExtension($name, $url, $path)){
			return false;
		}

		if(!$this->config->write()){
			return false;
		}

		$hook->run(Hook::AFTER_INSTALL);

		return true;
	}

	public function uninstall(string $name): bool
	{
		$path = $this->config->getToolsPath("/extensions/$name");

		$shellPath = new ShellPath($this->config);
		$shellPath->remove($path);

		// NOTE: BE VERY CAREFUL HERE!
		if($path == "/" || strpos($path, ".") === 0 || strpos($path, "extensions") === false){
			throw new Exception("Refusing to work with this path, it's dangerous");
		}

		if(!is_dir($path)){
			throw new DirectoryNotExistException($path);
		}

		$git = new Git();
		if($git->exists($path)){
			$cmd = "rm -rf $path";
			Text::print("{red}WARNING: BE CAREFUL THE PATH IS CORRECT{end}\n");
			$answer = CLI::ask("Should we remove the extension directory with the command '$cmd'?",["yes"]);

			if($answer === "yes") {
				Shell::passthru($cmd);
			}

			if($this->config->removeExtension($name)){
				return $this->config->write();
			}

			return true;
		}

		return false;
	}

	public function update(string $name): bool
	{
		$path = $this->config->getToolsPath("/extensions/$name");

		$repo = new Git();
		if($repo->exists($path)){
			$extensionConfig = new ExtensionConfig($path);

			Text::print("Pulling branch '{yel}".$repo->branch($path)."{end}' from repository '{yel}".$repo->remote($path)."{end}'\n");
			$repo->pull($path);

			$hook = new Hook($extensionConfig);
			$hook->run(Hook::AFTER_PULL);
			
			Text::print("Pushing branch '{yel}".$repo->branch($path)."{end}' to repository '{yel}".$repo->remote($path)."{end}'\n");
			$repo->push($path);

			return true;
		}

		return false;
	}

	public function list(): array
	{
		return array_map(function($file){
			$config = new ExtensionConfig($file);
			
			return [
				'name' => $config->getName(),
				'path' => dirname($file),
			];
		}, glob($this->config->getToolsPath("/extensions/*/" . ExtensionConfig::FILENAME)));
	}
}
