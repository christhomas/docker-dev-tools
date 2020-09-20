<?php
class Git{
	public function __construct()
	{

	}

	public function exists(string $dir): bool
	{
		if(is_dir($dir)){
			$this->status($dir);

			return Shell::getExitCode() === 0;
		}

		return false;
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
	public function pull(string $dir, bool $quiet=false): bool
	{
		if(!is_dir($dir)){
			throw new DirectoryMissingException("The directory '$dir' does not exist");
		}

		$quiet = $quiet ? "&>/dev/null": "";

		return Shell::passthru("git -C $dir pull $quiet") === 0;
	}

	public function push(string $dir, bool $quiet=false): bool
	{
		if(!is_dir($dir)){
			throw new DirectoryMissingException("The directory '$dir' does not exist");
		}

		$quiet = $quiet ? "&>/dev/null": "";

		return Shell::passthru("git -C $dir push $quiet") === 0;
	}

	public function status(string $dir): string
	{
		$output = implode("\n",Shell::exec("git -C $dir status -s"));
		$output = trim($output);

		return $output;
	}

	public function branch(string $dir): string
	{
		return Shell::exec("git -C $dir rev-parse --abbrev-ref HEAD", true);
	}

	public function fetch(string $dir, bool $prune=false): bool
	{
		$prune = $prune ? "-p" : "";

		Shell::exec("git -C $dir fetch $prune");

		return Shell::getExitCode() === 0;
	}
}
