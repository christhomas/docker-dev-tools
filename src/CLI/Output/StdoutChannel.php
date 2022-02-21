<?php declare(strict_types=1);

namespace DDT\CLI\Output;

class StdoutChannel extends Channel
{
    public function __construct()
    {
        parent::__construct('stdout');
    }
}