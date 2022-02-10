<?php
namespace DDT\Exceptions\Filesystem;

class DirectoryNotExistException extends \Exception
{
    public function __construct(string $dir, $code = 0, \Throwable $previous = null)
    {
        parent::__construct("The directory '$dir' does not exist", $code, $previous);
    }
};
