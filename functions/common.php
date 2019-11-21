<?php
define("__TOOL_DIR__", realpath(__DIR__ . '/../../'));

$colours = parse_ini_file(__DIR__ . '/colours.ini');
foreach($colours as $key => $val){
	if(strpos($val, '\033') === 0){
		$val = "\e".str_replace('\033', '', $val);
	}

	if(strpos($val, "\x") === 0){
		$val = array_filter(explode("\x", $val));
		$val = hex2bin(implode("", $val));
	}

	define(strtoupper($key), "$val ");
}

function tool_start($title, $subtitle)
{
	print(BLU . $title . END . ": " . YEL . $subtitle . END . "\n\n");
}

function tool_fatal_error($text, $die=true)
{
	$text = RED . $text . END . "\n";

	$die ? die($text) : print($text);
}

function script_passthru($directory, $command)
{
	$command = preg_replace('!\s+!', ' ', $command);

	$exec = "cd $directory && $command";

	if(!__EXEC__){
		print("Execution disabled, skipping: '$exec'\n");
		return;
	}

	if(__DEBUG__){
		print("Executing: $exec\n");
	}

	passthru($exec);
}

function read_json($file)
{
	return json_decode(file_get_contents($file),true);
}

function is_assoc_array(array $array) {
	return count(array_filter(array_keys($array), 'is_string')) > 0;
}
