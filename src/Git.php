<?php
class Git{
	public function __construct()
	{

	}

	/**
	 * @param string $url
	 * @param string $dir
	 * @return bool
	 * @throws DirectoryExistsException
	 */
	public function clone(string $url, string $dir): bool
	{
		if(is_dir($dir)){
			throw new DirectoryExistsException("The directory '$dir' already exists");
		}

		return Shell::passthru("git clone $url $dir") === 0;
	}

	/**
	 * @param string $dir
	 * @return bool
	 * @throws DirectoryMissingException
	 */
	public function pull(string $dir): bool
	{
		if(!is_dir($dir)){
			throw new DirectoryMissingException("The directory '$dir' does not exist");
		}

		return Shell::passthru("git -C $dir pull") === 0;
	}
}
