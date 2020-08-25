<?php
class Git{
	protected $dir;

	public function __construct(string $dir)
	{
		$this->dir = $dir;
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
}
