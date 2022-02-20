<?php declare(strict_types=1);

namespace DDT;

class Debug
{
    static public $enabled = false;

    static public function dump($mixed)
    {
        if(self::$enabled){
            is_scalar($mixed) ? print("$mixed\n") : var_dump($mixed);
        }
    }
}