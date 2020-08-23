<?php
class Format
{
	static public function ping(array $result, bool $buffer=false): string
	{
		$output = [];

		if($result['status'] === true){
			$output[] = "Ping: {grn}SUCCESS{end}";
		}else{
			$output[] = "Ping: {red}FAILURE{end}";
		}

		if($result['hostname'] && $result['hostname'] !== $result['ip_address']){
			$output[] = "Hostname: '{yel}{$result['hostname']}{end}'";
		}

		if($result['matched'] === true){
			$output[] = "Resolution Match: {grn}SUCCESS{end}";
		}else{
			$output[] = "Resolution Match: {red}FAILURE{end} (Expected: {$result['matched']})";
		}

		if($result['ip_address']){
			$output[] = "IP Address: '{yel}{$result['ip_address']}{end}'";
		}

		if($result['packet_loss'] === 0.0 && $result['can_resolve'] === true){
			$output[] = "Status: {grn}SUCCESS{end}";
		}else{
			$output[] = "Status: {red}FAILURE{end}";
		}

		if($result['can_resolve'] === true){
			$output[] = "Can Resolve: {grn}YES{end}";
		}else{
			$output[] = "Can Resolve: {red}NO{end}";
		}

		$output = "\n".implode("\n", $output)."\n";

		if($buffer === false) Text::print($output);

		return $output;
	}

	static public function networkList(array $networkList, string $format='pretty-print', bool $buffer=false): string
	{
		$output = [];

		foreach($networkList as $network){
			switch($format){
				case 'true':
				case 'pretty-print':
					$output[] = " - $network";
					break;

				case 'no-pretty':
					$output[] = "$network";
					break;

				case 'csv':
					$output[] = $network;
					break;
			}
		}

		if($format === 'csv'){
			$sep = ",";
		}else{
			$sep = "\n";
			if(empty($output)) $output[] = "There are no networks";
			array_unshift($output, "{blu}Docker Networks{end}:");
			$output[] = "\n";
		}

		$output = "\n".implode($sep, $output);

		if($buffer === false) Text::print($output);

		return $output;
	}

	static public function upstreamList(array $upstreamList, bool $buffer=false): string
	{
		$output = [];

		$output[] = "{blu}Domains that are registered in the proxy:{end}";

		if(!empty($upstreamList))
		{
			$table = new TextTable();
			$table->setRightPadding(10);
			$table->addRow(['Container Id', 'Host', 'Port', 'Path', 'Networks']);

			foreach($upstreamList as $container => $upstream){
				$table->addRow([$container, $upstream['host'], $upstream['port'], $upstream['path'], $upstream['networks']]);
			}

			$output[] = $table->render(true);
		}else{
			$output[] = " -- There are no domains yet -- \n";
		}

		$output = implode("\n", $output)."\n";

		if($buffer === false) Text::print($output);

		return $output;
	}
}
