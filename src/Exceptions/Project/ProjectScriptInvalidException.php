<?php
namespace DDT\Exceptions\Project;

class ProjectScriptInvalidException extends \Exception
{
    public function __construct(string $group, string $project, string $script, $code = 0, \Throwable $previous = null)
    {
        parent::__construct("The group '$group' and project '$project' does not have a valid commandline for script '$script'", $code, $previous);
    }
};
