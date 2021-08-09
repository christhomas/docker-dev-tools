<?php declare(strict_types=1);

namespace DDT\Tool;

use DDT\CLI;
use DDT\Config\SystemConfig;

class StatusTool extends Tool
{
    /** @var \DDT\Config\SystemConfig  */
    private $config;

    public function __construct(CLI $cli, SystemConfig $config)
    {
    	parent::__construct('status', $cli);

        $this->config = $config;
        $this->setDefaultHandler([$this, 'main']);
    }

    public function getTitle(): string
    {
        return 'Status';
    }

    public function getShortDescription(): string
    {
        return 'A tool to provide a quick status breakdown of resources';
    }

    public function getDescription(): string
    {
		return "This tool will provide a series of status feedbacks from various tools like the proxy, the dns, and various installed projects, with the ability to call specific groups of projects";
    }

    public function getExamples(): string
    {
        $entrypoint = $this->cli->getScript(false) . " " . $this->getName();

        return implode("\n", [
            "{yel}Usage Example:{end} $entrypoint - Will return status information the proxy, the dns, and for every project in every group (could be a large list)",
            "{yel}Usage Example:{end} $entrypoint <group> - Will return status information for the proxy, the dns, and a specific group of projects",
        ]);   
    }

    public function main()
    {
        \Script::failure('TODO: please implement me');
    }
}

// FIXME: This code is being moved into project configurations which means it's broken for now
// $projectConfig = $systemConfig->getProjectConfig('api-server');

// $config = $projectConfig; //new \DDT\Config\ProjectConfig();

// Script::title("Healthchecks", "Hopefully nobody died.....");

// $debug = $cli->hasArg("debug");

// foreach($argv as $arg){
//     if($arg === "--list"){
// 		$list = $config->listHealthcheck();
// 		print("Listing Healthchecks...\n");
// 		foreach($list as $healthcheck){
// 		    print("\t$healthcheck\n");
//         }
// 		die();
//     }
// }

// $list = $config->listHealthcheck();
// $table = new TextTable();
// $table->setRightPadding(5);

// foreach($list as $name){
// 	$healthcheck = $config->getHealthcheck($name);

// 	$result = $healthcheck->run();

//     $url = $result['url'];

// 	$access = $result['connected']
//         ? Text::green(Text::checkIcon() . " Connected")
//         : Text::red(Text::crossIcon() . " Failed");

// 	if(array_key_exists('http_code', $result)){
// 		$http_status = $result['http_code'] === 200
// 			? Text::green(Text::checkIcon() . " HTTP Success ({$result['http_code']})")
// 			: Text::red(Text::crossIcon() . " HTTP Failed ({$result['http_code']})");
//     }else{
// 		$http_status = "";
//     }

// 	if(array_key_exists('success', $result)){
// 		if($result['success']){
// 			$data_ok = Text::green(Text::checkIcon() . " Data OK");
// 		}else{
// 			$data_ok = Text::red(Text::crossIcon() . " Data Failed");
// 		}
//     }else{
// 	    $data_ok = "";
//     }

// 	if(array_key_exists('db_status', $result) && is_array($result['db_status'])){
// 	    $db_ok = array_reduce(array_keys($result['db_status']), function($c, $i) use ($result){
// 	        $status = $result['db_status'][$i] === true ? Text::green($i) : Text::red($i);
//             return trim("$c, $status", " ,");
//         }, "");

// 		$data_ok = "$data_ok Databases ($db_ok)";
//     }

// 	$table->addRow([
// 		ucwords(str_replace(["-","_"], " ", $name)).":",
//         $access,
//         $http_status,
//         $data_ok,
//         "URL: $url",
//     ]);

// 	if($reasons = \DDT\Helper\Arr::get($result, 'reasons')){
// 		array_map(function($r) use ($table) {
// 			$table->addRow(["\t$r"], true);
// 		}, $reasons);
// 	}


// 	if(is_string($debug)){
// 		print("Server Replied: $debug\n");
// 	}
// }

// print($table->render(true));

// if(empty($list)){
//     print(Text::yellow("There were no healthchecks found, nothing to test\n"));
// }
