<?php
class Git{
	private $url;

	public function __construct(string $url)
	{
		$this->url = $url;
	}

	public function clone(string $dir)
	{
		if(is_dir($dir)){
			throw new DirectoryExistsException("The directory '$dir' already exists");
		}

		Execute::passthru("git clone $this->url $dir");
	}

	public function pull(string $dir)
	{
		if(!is_dir($dir)){
			throw new DirectoryMissingException("The directory '$dir' does not exist");
		}
	}
}
