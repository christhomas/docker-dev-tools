<?php declare(strict_types=1);

namespace DDT\Tool;

use DDT\CLI;
use DDT\Config\SystemConfig;

class ExtensionTool extends Tool
{
    /** @var \DDT\Config\SystemConfig  */
    private $config;

    public function __construct(CLI $cli, SystemConfig $config)
    {
    	parent::__construct('extension', $cli);

        $this->config = $config;
    }

    public function getTitle(): string
    {
        return 'Extension Management Tool';
    }

    public function getShortDescription(): string
    {
        return 'A tool to manage tool extensions and update them';
    }

    public function getDescription(): string
    {
		return "This tool will manage extensions installed within the tools. It can install, uninstall, or update them. At this time
        the tool only supports extensions from GIT repositories";
    }

    public function getExamples(): string
    {
        $entrypoint = $this->cli->getScript(false) . " " . $this->getName();

        return implode("\n", [
            "{yel}Usage Example:{end} $entrypoint {yel}install name url=https://github.com/something/extension_repo.git{end}",
            "{yel}Usage Example:{end} $entrypoint {yel}uninstall plista{end}"
        ]);   
    }

    public function getOptions(): string
	{
		$alias = $this->config->getKey('.ip_address') ?? 'unknown';

		return "\t" . implode("\n\t", [
            "--install=XXX: Will install a new extension using the parameters value as the given name",
            "--url=XXX: Will use this location to install the extension, only git repositories are supported",
            "--uninstall=XXX: Will uninstall an extension with the given name",
            "--list: Will list the installed extensions",
            "--update: Will update all extensions from their repository urls given during installation",
		]);
	}

    public function install(string $name, string $url)
    {
        \Script::failure('TODO: tool command: '.__METHOD__." is not implemented");
        /*
        $name   = $cli->getArgWithVal('install');
        $url    = $cli->getArgWithVal('url');

        $config = \DDT\Config\SystemConfig::instance();

        if($name && $url){
            Text::print("Installing new ExtensionManager '{yel}$name{end}' from url '{yel}$url{end}'\n");
            try{
                $extension = new ExtensionManager($config);
                if($extension->install($name, $url)){
                    Script::success("Extension '$name' was installed");
                }else{
                    Script::failure("Extension '$name' failed to install");
                }
            }catch(DirectoryExistsException $e){
                Script::failure("Can't install extension '$name' because there is already an extension installed with that name");
            }
        }*/
    }

    public function uninstall(string $name)
    {
        \Script::failure('TODO: tool command: '.__METHOD__." is not implemented");
        /*
        if($name = $cli->getArgWithVal("uninstall")){
            Text::print("Uninstalling extension '$name'\n");
        
            try{
                $extension = new ExtensionManager($config);
                if($extension->uninstall($name)){
                    Script::success("Extension '$name' was uninstalled");
                }else{
                    Script::failure("Extension '$name' has failed to uninstall");
                }
            }catch(DirectoryNotExistException $e){
                Script::failure("Can't uninstall extension '$name' as the directory that it was expected to be in was missing");
            }
        }*/        
    }

    public function update(string $name)
    {
        \Script::failure('TODO: tool command: '.__METHOD__." is not implemented");
        /*
        if($name = $cli->getArg("update")){
            try{
                $extension = new ExtensionManager($config);
        
                $list = [];
        
                if($name === 'true'){
                    $list = array_map(function($e){ 
                        return basename($e['path']);
                    }, $extension->list());
                }else{
                    $list = [$name];
                }
        
                foreach($list as $name){
                    Text::print("Updating extension '$name'\n");
                    if($extension->update($name)){
                        Script::success("Extension '$name' was updated");
                    }else{
                        Script::failure("Extension '$name' has failed to update");
                    }
                }
            }catch(DirectoryNotExistException $e){
                Script::failure("Can't update extension '$name' as the directory that it was expected to be in was missing");
            }
        }*/
    }

    public function list()
    {
        \Script::failure('TODO: tool command: '.__METHOD__." is not implemented");
        /*
        if($cli->hasArg("list")){
            try{
                $extension = new ExtensionManager($config);
                var_dump($extension->list());
            }catch(\DDT\Exceptions\Config\ConfigWrongTypeException $e){
                Script::failure($e->getMessage());
            }
        }*/        
    }
}