<?php declare(strict_types=1);

namespace DDT\Tool;

use DDT\CLI;
use DDT\Config\SystemConfig;
use DDT\Text\Text;

class SetupTool extends Tool
{
    /** @var Text */
    private $text;

    /** @var $home */
    private $home;

    /** @var int $maxBackups The maximum number of backups one of the shell config files can have before we should clean them up */
    private $maxBackups = 10;

	/** @var string[] An array of supported files that will be searched/installed into */
	private $files = [
		".bash_profile",
		".bashrc",
		".zshrc",
	];

    public function __construct(CLI $cli, Text $text, ?string $home=null)
    {
    	parent::__construct('setup', $cli);

        $this->text = $text;

        $this->home = $home ?? $_SERVER['HOME'];
        $this->files = $this->getExistingFiles($this->files);

        $this->registerCommand('install');
        $this->registerCommand('uninstall');
        $this->registerCommand('test');
        $this->registerCommand('setPath');
    }

    public function getExistingFiles(array $files): array
    {
        return array_filter(array_map(function($file){
			$file = "$this->home/$file";

			return file_exists($file) ? $file : null;
		}, $files));
    }

    public function getToolMetadata(): array
    {
        $entrypoint = $this->cli->getScript(false) . " " . $this->getToolName();

        return [
            'title' => 'Tool Setup and Configuration',
            'short_description' => 'A tool that manages the installation and upgrade of the docker dev tools',
            'description' => 'This tool that manages the installation of the docker dev tools',
            'examples' => implode("\n", [
                "  - $entrypoint install --path=\$HOME/projects/ddt-tools",
                "  - $entrypoint uninstall --path=\$HOME/projects/ddt-tools",
                "  - $entrypoint set-path --path=\$HOME/somewhere/else/if/you/want/ddt-tools",
            ]),
            'options' => implode("\n", [
                "  install --path=<path>: Install the tools into the path using the either the optional path given with the parameter or defaults to the current directory",
                "  uninstall --path=<path>: Uninstall the tools, given the path from the configuration",
                "  {yel}upgrade{end}: TODO: Should add this functionality",
                "  set-path --path=<path>: Update where the tools are installed",
                "  test: Open a new sub shell and test whether scripts work with the current system path.",
            ])
        ];
    }

    private function backupFile(string $filename): bool
	{
		$backupList = glob("{$filename}_*");

        if(count($backupList) > $this->maxBackups){
            $this->cli->debug("{red}[SETUP]:{end} There are too many backup files for {yel}'$filename'{end}, delete the first one {yel}'{$backupList[0]}'{end}\n");
            $this->cli->debug("{red}[SETUP]:{end} This backup cleanup functionality is not implemented yet\n");
        }

		$contents = file_get_contents($filename);

		foreach($backupList as $backup){
			$compare = file_get_contents($backup);

			if(strcmp($contents, $compare) === 0){
                $this->cli->debug("{red}[SETUP]:{end} This file contents of '$filename' were already backed up in the file '$backup'\n");
				return true;
			}
		}

		// make backup with timestamp and rand chars
		$backup = implode("_",[$filename, date("\DYmd_\THis"), bin2hex(random_bytes(4))]);
        $this->cli->print("Backing up file: '$filename' to '$backup'\n");
		
		return file_put_contents($backup, $contents) !== false;
	}

    private $found;
	private function add(string $newPath): void
	{
		$this->found = false;

		$this->processFiles(function($contents, $lineNum, $lineData) use ($newPath) {
			$pattern = "/^(?P<prefix>[\s]+)?PATH=(?P<path>.*)$/";

			if(strpos($lineData, "PATH=") !== false && preg_match($pattern, $lineData, $matches)){
				$path = explode(':',$matches['path']);
				foreach($path as $pseg => $pval){
					if($pval === $newPath){
						$this->found = true;
					}
				}
			}

			return $contents;
		}, function($contents) use ($newPath){
			if($this->found === false){
				$contents[] = "PATH=\$PATH:$newPath";
			}

			return $contents;
		});
	}

	private function remove(string $search): void
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
            $this->cli->print("Processing file '$file'\n");

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

    public function installCommand(string $path)
    {
        $this->cli->print("{blu}Docker Dev Tools Installer{end}\n");

        $path = rtrim($path, "/");

        // check if path exists
        if(is_dir($path)){
            $path = realpath($path);

            // if path exists, try to find 'ddt' script in the bin folder
            if(!is_dir("$path/bin") || !file_exists("$path/bin/ddt")){
                // if not, throw an error, the path was found, but the installation was not valid
                $this->cli->failure(implode("\n",[
                    "{red}Sanity checks for this path failed. The following items are required to be valid:",
                    "Folder: $path/bin",
                    "File: $path/bin/ddt{end}",
                ]));
            }
        }else if(is_file($path)){
            // if it's a file, we can't continue anymore, this is going to corrupt something
            $this->cli->failure("The path '$path' given was not a directory, cannot continue\n");
        }else{
            // if not, create it
            mkdir($path);
        }

        // for each shell configuration file we found
        foreach($this->files as $file){
            // backup the file
            $this->backupFile($file);
        }

        // TODO: I don't like how this processes all files at once, I would like this functionality removed
        // add the installation path from the file
        $this->add("$path/bin");

        // You must write a default ddt-system.json file to the $HOME directory
        // This is to store configuration from the tool system in a predictable 
        // place. It's not optional because without it, nothing else will run
        // Use the ConfigTool to get the job done
        /** @var ConfigTool */
        $configTool = $this->getTool('config');
        $this->cli->print($configTool->resetCommand());

        //////////////////////////////////////////////////////////////////
        // TODO: handle installation of extension bin paths
        //////////////////////////////////////////////////////////////////
        
        //  write into the config the tools path and save file
        $config = SystemConfig::instance();
		$config->setToolsPath($path);
		$config->write();

        $this->testCommand();
    }

    public function uninstallCommand(string $path)
    {
        $this->cli->print("{blu}Docker Dev Tools Uninstaller{end}\n");

        $path = rtrim($path, "/");

        // for each shell configuration file we found
        foreach($this->files as $file) {
            // backup the file
            $this->backupFile($file);
        }
		
        // TODO: I don't like how this processes all files at once, I would like this functionality removed
        // remove the installation path from the files
		$this->remove("$path/bin");

		// TODO: handle uninstallation of extension bin paths
    } 

    public function testCommand(): bool
    {
        $config = SystemConfig::instance();
        $toolPath = $config->getToolsPath();
        $script = $this->cli->getScript(false);

        $path = implode("\n",$this->cli->exec("bash --login -c 'echo \$PATH'"));
		$path = explode(":", $path);

		$toolPath = "$toolPath/bin";

		foreach($path as $segment){
			if($toolPath === $segment){
				try {
					$this->cli->exec("bash --login -c '$script --help'");
					$this->cli->exec("bash -c '$toolPath/$script --help'");
                    $this->cli->box("The path was successfully installed, you might need to open a new terminal to see the effects", "blk", "grn");
					return true;
				}catch(\Exception $e){
					$this->cli->print($e->getMessage());
                    $this->cli->box("The tool '$script' could not set the shell path successfully installed. Please report this error", "wht", "red");
					return false;
				}
			}
		}

		return false;
    }

    public function setPathCommand(string $path)
    {
        $path = rtrim($path, "/");

        $this->cli->print("{blu}Set Tools Path:{end} updating configuration with path '$path'\n");
        $config = SystemConfig::instance();
        $config->setToolsPath($path);
	    $config->write();
    }
}
