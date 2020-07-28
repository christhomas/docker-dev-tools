<?php
class Script
{
	static function title($title, $subtitle)
	{
		Text::print("{quiet}{blu}$title{end}: {yel}$subtitle{end}\n\n{/quiet}");
	}

	static function die(?string $text=null, int $exitcode=0)
	{
		$colour	= $exitcode === 0 ? "{grn}" : "{red}";
		$where	= $exitcode === 0 ? STDOUT : STDERR;

		if($text !== null){
			fwrite($where, Text::write($colour.rtrim($text, "\n")."{end}\n"));
		}

		exit($exitcode);
	}

	static function success(?string $text=null)
	{
		self::die($text, 0);
	}

	static function failure(?string $text=null)
	{
		self::die($text, 1);
	}
}
