<?php declare(strict_types=1);

namespace DDT\CLI\Output;

class DebugChannel extends Channel
{
    public function __construct()
    {
        parent::__construct('debug');
    }
}