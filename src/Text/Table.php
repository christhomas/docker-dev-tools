<?php declare(strict_types=1);

namespace DDT\Text;

class Table
{
    private $text;
	private $data;
	private $tabWidth = 2;
	private $numColumns = 0;
	private $space = " ";
	private $rightPadding = 0;
	private $debug = false;

    public function __construct(Text $text, ?array $data=[], int $tabWidth=2)
    {
        $this->text = $text;
		$this->data = $data;
		$this->tabWidth = $tabWidth;
		$this->numColumns = 0;
	}

	public function setDebug(bool $state)
	{
		$this->debug = $state;
		$this->space = $state ? '_' : ' ';
	}

	public function debug($data)
	{
		if($this->debug){
			print("$data\n");
		}
	}

    public function var_dump($data)
    {
        if($this->debug){
			var_dump($data);
		}
    }

	public function addRow($data) {
		if(count($data) > $this->numColumns){
			$this->numColumns = count($data);
		}

		$this->data[] = $data;
	}

	public function setRightPadding(int $padding = 0)
	{
		$this->rightPadding = $padding;
	}

	private function fixColumnWidths($columns): array
	{
		// Replace tabs with spaces
		$columns = array_map(function($text) {
			$replace = str_pad("", $this->tabWidth, $this->space);
			return str_replace("\t", $replace, $text);
		}, $columns);

		// Replace all special codes with shell script codes
		$columns = array_map(function($text) {
			return $this->text->write($text);
		}, $columns);

		// Find the printing character widths for every column
		$pw = array_reduce($columns, function($accum, $text) {
			$tw = strlen($text);
			$np = 0;

			$codes = $this->text->findCodes($text);
			foreach($codes as $c){
				$tw = $tw + $c['printing'];
				$np = $np + $c['length'];
			}

			$accum[] = $tw - $np;
			return $accum;
		}, []);

		// What is the minimum column width?
		$min = max($pw);

		// Determine the padding PER COLUMN, because each column contains a unique number of non-printing characters
		$padding = array_reduce($columns, function($accum, $text) use ($pw, $min) {
			$ci = count($accum);
			$tw = strlen($text);
			$np = $tw - $pw[$ci];
		
			$accum[] = $min + $np;
			return $accum;
		}, []);

		// Pad each column according to it's own requirements
		$columns = array_map(function($ci, $ct) use ($padding) {
			return str_pad($ct, $padding[$ci] + $this->rightPadding, $this->space);
		}, array_keys($columns), $columns);

		return $columns;
	}

	public function render($buffer=false): ?string
	{
		if(empty($this->data)) return null;

		$transpose = function($array) {
			array_unshift($array, null);
			return call_user_func_array('array_map', $array);
		};

		// Flip the array to process columns easily
		$data = $transpose($this->data);
		foreach($data as $index => $columns){
			$data[$index] = $this->fixColumnWidths($columns);
		}

		// Flip the array back to normal
		$data = $transpose($data);

		// Output the data as lines of strings
		$output = "";
		foreach($data as $row){
			// we are done with this line now, finalise it's output somewheres
			// implode, buffer, print
			$row = implode(" ", $row) . "\n";

			if($buffer){
				$output .= $row;
			}else{
				print($row);
			}
		}

		return strlen($output) ? $output : null;
	}
}
