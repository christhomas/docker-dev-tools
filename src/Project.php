<?php
class Project
{
	protected $name;
	protected $url;
	protected $branch;
	protected $directory;

	public function __construct(string $name, string $url, string $branch, string $directory)
	{
		$pattern = "[0-9a-z\-\_]+";

		if(!preg_match("/$pattern/", $name)){
			throw new InvalidArgumentException("the project name '$name' should match regex '$pattern'");
		}

		if(Git::exists($url) === false){
			throw new InvalidArgumentException("the git url '$url' is not a valid git repository");
		}

		$branchPattern = "[0-9a-z\-\_\/]+";
		if(!preg_match("/$pattern/", $branch)){
			throw new InvalidArgumentException("the branch name '$branch' should match regex '$branchPattern'");
		}

		if(!preg_match("/$pattern/", $directory)){
			throw new InvalidArgumentException("the destination directory '$directory' should match regex '$pattern'");
		}

		$this->name = $name;
		$this->url = $url;
		$this->branch = $branch;
		$this->directory = $directory;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getURL(): string
	{
		return $this->url;
	}

	public function getBranch(): string
	{
		return $this->branch;
	}

	public function getDirectory(): string
	{
		return $this->directory;
	}
}
