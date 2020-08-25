<?php
class Git{
	protected $dir;

	public function __construct(string $dir)
	{
		$this->dir = $dir;
	}

	static public function exists(string $url)
	{
		try{
			Shell::exec("git ls-remote -h $url");
			return true;
		}catch(Exception $e){
			return false;
		}
	}

	/**
	 * @param string $url
	 * @param string $dir
	 * @return bool
	 * @throws DirectoryExistsException
	 */
	public function clone(string $url): bool
	{
		if(is_dir($this->dir)){
			throw new DirectoryExistsException("The directory '$this->dir' already exists");
		}

		if(Git::exists($url) === false){
			throw new InvalidArgumentException("The url '$url' is not a valid git repository");
		}

		return Shell::passthru("git clone $url $this->dir") === 0;
	}

	/**
	 * @param string $dir
	 * @return bool
	 * @throws DirectoryMissingException
	 */
	public function pull(): bool
	{
		if(!is_dir($this->dir)){
			throw new DirectoryMissingException("The directory '$this->dir' does not exist");
		}

		return Shell::passthru("git -C $this->dir pull") === 0;
	}

	public function remote(?string $name = 'origin'): string
	{
		return Shell::exec("git -C $this->dir remote get-url $name", true);
	}

	public function branch(): string
	{
		return Shell::exec("git -C $this->dir rev-parse --abbrev-ref HEAD", true);
	}
}
