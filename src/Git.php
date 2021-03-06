<?php
class Git{
	public function __construct()
	{

	}

	static public function exists(string $url): bool
	{
		try{
			Shell::exec("git ls-remote -h $url");
			return true;
		}catch(Exception $e) {
			return false;
		}
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
			throw new DirectoryExistsException($dir);
		}

		if(Git::exists($url) === false){
			throw new InvalidArgumentException("The url '$url' is not a valid git repository");
		}

		return Shell::passthru("git clone $url $dir") === 0;
	}

	/**
	 * @param string $dir
	 * @return bool
	 * @throws DirectoryNotExistException
	 */
	public function pull(string $dir, bool $quiet=false): bool
	{
		if(!is_dir($dir)){
			throw new DirectoryNotExistException($dir);
		}

		$quiet = $quiet ? "&>/dev/null": "";

		return Shell::passthru("git -C $dir pull $quiet") === 0;
	}

	public function push(string $dir, bool $quiet=false): bool
	{
		if(!is_dir($dir)){
			throw new DirectoryNotExistException($dir);
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

	public function remote(string $dir, string $name='origin'): string
	{
		return Shell::exec("git -C $dir remote get-url $name", true);
	}

	public function fetch(string $dir, bool $prune=false): bool
	{
		$prune = $prune ? "-p" : "";

		Shell::exec("git -C $dir fetch $prune");

		return Shell::getExitCode() === 0;
	}
}
