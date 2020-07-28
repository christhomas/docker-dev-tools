<?php
$colours = parse_ini_file(__DIR__ . '/colours.ini');
foreach($colours as $key => $val){
	if(strpos($val, '\033') === 0){
		$val = "\e".str_replace('\033', '', $val);
	}

	if(strpos($val, "\x") === 0){
		$val = array_filter(explode("\x", $val));
		$val = hex2bin(implode("", $val));
	}

	$key = strtoupper($key);
	define($key, $val);

	if(!in_array($key, ['chk', 'mss', 'wrn']) && class_exists(Text::class)){
		Text::addCode($key, $val);

		$map = [
			"BLK"=>"BLACK",
			"RED"=>"RED",
			"GRN"=>"GREEN",
			"YEL"=>"YELLOW",
			"BLU"=>"BLUE",
			"MAG"=>"MAGENTA",
			"CYN"=>"CYAN",
			"WHT"=>"WHITE"
		];

		$key = str_replace(array_keys($map), array_values($map), $key);
		Text::addCode($key, $val);
	}
}
