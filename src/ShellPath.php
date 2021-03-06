<?php
class ShellPath
{
	/** @var string The home directory of this computer */
	private $home;

	/** @var string[] An array of supported files that will be searched/installed into */
	private $files = [
		".bash_profile",
		".bashrc",
		".zshrc",
	];

	/**
	 * Path constructor.
	 * @param string $home The home directory of this computer
	 * @param string $path The path to the tools to setup
	 */
	public function __construct(SystemConfig $config, ?string $home=null)
	{
		$this->config = $config;

		$home = $home ?: $_SERVER['HOME'];

		$this->home = $home;

		$this->files = array_filter(array_map(function($file) use ($home){
			$file = "$home/$file";

			return file_exists($file) ? $file : null;
		}, $this->files));
	}

	public function install(string $path): void
	{
		// Detect bad paths and ignore them
		$path = is_dir($path) ? realpath($path) : dirname(__DIR__);
		
		$script = "ddt-setup";

		if(!is_dir("$path/bin") || !file_exists("$path/bin/$script")){
			Script::failure(implode("\n",[
				"{red}Sanity checks for this path failed. The following items are required to be valid:",
				"Folder: $path/bin",
				"File: $path/bin/$script{end}",
			]));
		}
	
		foreach($this->files as $file) $this->backupFile($file);

		$this->add("$path/bin");

		$extensions = new ExtensionManager($this->config);
		$list = $extensions->list();

		foreach($list as $e){
			$this->add($e);
		}

		$this->config->setToolsPath($path);
		$this->config->write();
	}

	public function uninstall($path): void
	{
		foreach($this->files as $file) $this->backupFile($file);
		
		$this->remove("$path/bin");

		$extensions = new ExtensionManager($this->config);
		$list = $extensions->list();

		foreach($list as $e){
			$this->remove($e);
		}
	}

	private $found;
	public function add(string $newPath): void
	{
		$self = $this;

		$this->found = false;

		$this->processFiles(function($contents, $lineNum, $lineData) use ($self, $newPath) {
			$pattern = "/^(?P<prefix>[\s]+)?PATH=(?P<path>.*)$/";

			if(strpos($lineData, "PATH=") !== false && preg_match($pattern, $lineData, $matches)){
				$path = explode(':',$matches['path']);
				foreach($path as $pseg => $pval){
					if($pval === $newPath){
						$self->found = true;
					}
				}
			}

			return $contents;
		}, function($contents) use ($self, $newPath){
			if($self->found === false){
				$contents[] = "PATH=\$PATH:$newPath";
			}

			return $contents;
		});
	}

	public function remove(string $search): void
	{
		$this->processFiles(function($contents, $lineNum, $lineData) use ($search) {
			$pattern = "/^(?P<prefix>[\s]+)?PATH=(?P<path>.*)$/";

			if(strpos($lineData, "PATH=") !== false && preg_match($pattern, $lineData, $matches)){
				$path = explode(':',$matches['path']);
				foreach($path as $pseg => $pval){
					if($pval === $search){
						unset($path[$pseg]);
					}
				}
				if(count($path) === 1 && current($path) === "\$PATH"){
					unset($contents[$lineNum]);
				}else{
					$contents[$lineNum] = "{$matches['prefix']}PATH=".implode(":", $path);
				}
			}

			return $contents;
		});
	}

	private function processFiles(callable $callback, callable $after=null): void
	{
		foreach($this->files as $file){
			// read file contents
			$contents = file_get_contents($file);

			// explode into lines and process each ones
			$contents = explode("\n", $contents);
			foreach($contents as $num => $line){
				$contents = $callback($contents, $num, $line);
			}
			if(is_callable($after)){
				$contents = $after($contents);
			}

			// recombine all lines together and write the file back
			$contents = implode("\n", $contents);
			$contents = preg_replace('/\n{2,}/m',"\n\n",$contents);
			file_put_contents($file, trim($contents,"\n")."\n");
		}
	}

	public function backupFile(string $filename): bool
	{
		$backupList = glob("{$filename}_*");

		$contents = file_get_contents($filename);

		foreach($backupList as $backup){
			$compare = file_get_contents($backup);

			if(strcmp($contents, $compare) === 0){
				return true;
			}
		}

		// make backup with timestamp and rand chars
		$backup = implode("_",[$filename, date("\DYmd_\THis"), bin2hex(random_bytes(4))]);
		print("Backing up file: '$filename' to '$backup'\n");
		
		return file_put_contents($backup, $contents) !== false;
	}

	public function stripString(string $string): void
	{
		$this->processFiles(function($contents, $lineNum, $lineData) use ($string) {
			if($lineData !== $string){
				unset($contents[$lineNum]);
			}

			return $contents;
		});
	}

	public function stripStringByRegex(string $pattern): void
	{
		$this->processFiles(function($contents, $lineNum, $lineData) use ($pattern) {
			if(preg_match($pattern, $lineData, $matches)){
				unset($contents[$lineNum]);
			}

			return $contents;
		});
	}

	public function test(string $toolPath, string $script): bool
	{
		$path = implode("\n",Shell::exec("bash --login -c 'echo \$PATH'"));
		$path = explode(":", $path);

		$toolPath = "$toolPath/bin";

		foreach($path as $segment){
			if($toolPath === $segment){
				try {
					Shell::exec("bash --login -c '$script --help'");
					Shell::exec("bash -c '$toolPath/$script --help'");
					return true;
				}catch(Exception $e){
					Text::print($e->getMessage());
					return false;
				}
			}
		}

		return false;
	}
}
