<?php
namespace DDT\Exceptions\Project;

class ProjectNotFoundException extends \Exception
{
    public function __construct(string $group, string $project, $code = 0, \Throwable $previous = null)
    {
        parent::__construct("The project '$project' does not exist in group '$project' or some other unknown error occurred", $code, $previous);
    }
};
