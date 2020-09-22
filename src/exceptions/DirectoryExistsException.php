<?php
class DirectoryExistsException extends Exception{
    public function __construct(string $dir, int $code = 0, \Throwable $previous = null){
        parent::__construct("The directory '$dir' already exists", $code, $previous);
    }
};
