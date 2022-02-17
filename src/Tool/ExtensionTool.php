<?php declare(strict_types=1);

namespace DDT\Tool;

use DDT\CLI;
use DDT\Config\SystemConfig;
use DDT\Exceptions\Config\ConfigWrongTypeException;
use DDT\Extension\ExtensionManager;
use DDT\Text\Table;

class ExtensionTool extends Tool
{
    /** @var ExtensionManager */
    private $extensionManager;

    /** @var SystemConfig */
    private $config;

    public function __construct(CLI $cli, ExtensionManager $extensionManager, SystemConfig $config)
    {
    	parent::__construct('extension', $cli);

        $this->config = $config;
        $this->extensionManager = $extensionManager;

        $this->setToolCommand('install', 'installCommand');
        $this->setToolCommand('uninstall', 'uninstallCommand');
        $this->setToolCommand('update', 'updateCommand');
        $this->setToolCommand('list', 'listCommand');
    }

    public function getToolMetadata(): array
    {
        $entrypoint = $this->cli->getScript(false) . " " . $this->getToolName();

        return [
            'title' => 'Extension Management Tool',
            'short_description' => 'A tool to manage tool extensions and update them',
            'description' => trim(
                "This tool will manage extensions installed within the tools. It can install, uninstall,\n".
                "or update them. At this time the tool only supports extensions from GIT repositories\n"
            ),
            'examples' => [
                "{yel}Usage Example:{end} $entrypoint {yel}install --name=example url=https://github.com/something/extension_repo.git{end}",
                "{yel}Usage Example:{end} $entrypoint {yel}uninstall --name=example{end}",
            ],
            'options' => [
                "install --name=<name> --url=<url>: Will install a new extension, requires two parameters, --name and --url, only git urls are supported",
                "uninstall --name<name>: Will uninstall an extension with the given name",
                "list: Will list the installed extensions",
                "update: Will update all extensions from their repository urls given during installation",
            ]
        ];
    }

    public function installCommand(string $name, string $url)
    {
        $this->cli->print("Installing new ExtensionManager '{yel}$name{end}' from url '{yel}$url{end}'\n");

        /*
        $name   = $cli->getArgWithVal('install');
        $url    = $cli->getArgWithVal('url');

        $config = \DDT\Config\SystemConfig::instance();

        if($name && $url){
            $this->cli->print("Installing new ExtensionManager '{yel}$name{end}' from url '{yel}$url{end}'\n");
            try{
                $extension = new ExtensionManager($config);
                if($extension->install($name, $url)){
                    $this->cli->success("Extension '$name' was installed");
                }else{
                    $this->cli->failure("Extension '$name' failed to install");
                }
            }catch(DirectoryExistsException $e){
                $this->cli->failure("Can't install extension '$name' because there is already an extension installed with that name");
            }
        }*/
    }

    public function uninstallCommand(string $name)
    {
        $this->cli->failure('TODO: tool command: '.__METHOD__." is not implemented");
        /*
        if($name = $cli->getArgWithVal("uninstall")){
            $this->cli->print("Uninstalling extension '$name'\n");
        
            try{
                $extension = new ExtensionManager($config);
                if($extension->uninstall($name)){
                    $this->cli->success("Extension '$name' was uninstalled");
                }else{
                    $this->cli->failure("Extension '$name' has failed to uninstall");
                }
            }catch(DirectoryNotExistException $e){
                $this->cli->failure("Can't uninstall extension '$name' as the directory that it was expected to be in was missing");
            }
        }*/        
    }

    public function updateCommand(string $name)
    {
        $this->cli->failure('TODO: tool command: '.__METHOD__." is not implemented");
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
                    $this->cli->print("Updating extension '$name'\n");
                    if($extension->update($name)){
                        $this->cli->success("Extension '$name' was updated");
                    }else{
                        $this->cli->failure("Extension '$name' has failed to update");
                    }
                }
            }catch(DirectoryNotExistException $e){
                $this->cli->failure("Can't update extension '$name' as the directory that it was expected to be in was missing");
            }
        }*/
    }

    public function listCommand()
    {
        // get list of configured extensions
        // get list of extensions from the filesystem
        // foreach configured extension, test whether things work
        // when an extension is found, remove it from the list of extensions in the filesystem
        // the remaining extensions from the filesystem, are they executable?
        // we should show a table of information about the state of each extension found
        // we do like a venn diagram of configured and installed extensions regarding their status
        try{
            $extensionList = $this->extensionManager->list();

            /** @var Table $table */
            $table = container("table");
            $table->setRightPadding(10);
            // table headers
            $table->addRow(["{yel}Name{end}", "{yel}Url{end}", "{yel}Path{end}"]);
            // table body
            foreach($extensionList as $name => $e){
                $table->addRow([$name, $e['url'], $e['path']]);
            }

            return $table->render(true);
        }catch(ConfigWrongTypeException $e){
            $this->cli->failure($e->getMessage());
        }
    }
}