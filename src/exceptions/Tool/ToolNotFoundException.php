<?php
namespace DDT\Exceptions\Tool;

class ToolNotFoundException extends \Exception
{
    public function __construct(string $tool, int $code = 0, \Throwable $previous=null)
    {
        parent::__construct("The tool '$tool' was not found", $code, $previous);
    }
}