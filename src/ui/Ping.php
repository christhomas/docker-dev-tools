<?php declare(strict_types=1);

namespace DDT\ui;

use DDT\Network\Address;

class Ping
{
    private static $output = '';

    public static function render(Address $address)
    {
        self::$output = '';

        $temp = [];

        if($address->status){
			$temp[] = "Ping: {grn}SUCCESS{end}";
		}else{
			$temp[] = "Ping: {red}FAILURE{end}";
		}

		if($address->hostname !== null){
			$temp[] = "Hostname: '{yel}{$address->hostname}{end}'";
		}

		if($address->ip_address !== null){
			$temp[] = "IP Address: '{yel}{$address->ip_address}{end}'";
		}

		if($address->packet_loss === 0.0 && $address->can_resolve === true){
		    $temp[] = "Status: {grn}SUCCESS{end}";
		}else{
			$temp[] = "Status: {red}FAILURE{end}";
		}

		if($address->can_resolve === true){
			$temp[] = "Can Resolve: {grn}YES{end}";
		}else{
		    $temp[] = "Can Resolve: {red}NO{end}";
		}

		return self::$output = implode(", ", $temp) . "\n";
    }

    public static function get(): string
    {
        return self::$output;
    }
}