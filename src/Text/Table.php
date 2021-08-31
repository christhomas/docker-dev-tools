<?php declare(strict_types=1);

namespace DDT\Text;

class Table
{
    private $text;
	private $data;
	private $tabWidth = 2;
	private $space = " ";
	private $rightPadding = 0;
	private $debug = false;

    public function __construct(Text $text, ?array $data=[], int $tabWidth=2)
    {
        $this->text = $text;
		$this->data = $data;
		$this->tabWidth = $tabWidth;
	}

	public function setDebug(bool $state)
	{
		$this->debug = $state;

		if($state) $this->space = "_";
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
		foreach($data as $c => $column){
			$replace = str_pad("", $this->tabWidth, $this->space);
			$data[$c] = str_replace("\t", $replace, $column);
		}
		$this->data[] = $data;
	}

	private function adjustColumnWidthByNonPrintingChars($width, $text)
	{
		// Find all non text colour and non printing character codes
		$codes = $this->text->findCodes($text);

		$this->debug("Found ".count($codes)." codes");

		$width = array_reduce($codes, function($c, $i){
			// calculate the amount of characters we need to subtract
			$l = strlen($i['value']);
			// if character is printing, add back a number of characters (normally icons, are 2 characters in width)
			$p = $i['printing'];
			$subtract = $l - $p;
			$new_c = $c - $subtract;
			$this->debug("subtracting($subtract): '$c' -> '$new_c' hex(".bin2hex($i['value']).") or print({$i['value']}) ($l, $p)");
			return $new_c;
		}, $width);

		return $width > 0 ? $width : 0;
	}

	public function setRightPadding(int $padding = 0)
	{
		$this->rightPadding = $padding;
	}

	public function render($buffer=false): ?string
	{
		if(empty($this->data)) return null;

		if(count($this->data) > 1){
			// This function rotates the array so that rows become columns and columns become rows
			$transpose = function($array) {
				array_unshift($array, null);
				return call_user_func_array('array_map', $array);
			};

			$flipped = $transpose($this->data);

			// start with zeroed column widths
			$colWidths = array_fill_keys(array_keys($flipped), 0);

			foreach($flipped as $c => $column) {
				// Every column starts with zero width
				$max = $colWidths[$c];

				foreach($column as $r => $string){
					// Only process rows that have more than one column
					// This ignores full width rows that span multiple columns
					if(count($this->data[$r]) > 1){
						// Get string length, adjust for hidden non printing characters
						$cw = strlen($string);
						$this->debug("col[$c] width before = $cw");
						$cw = $this->adjustColumnWidthByNonPrintingChars($cw, $string);
						$this->debug("col[$c] width after = $cw");
						// If length is greater than the max for this column, set this as the max
						if($cw > $max) $max = $cw;
					}
				}
				$colWidths[$c] = $max;
			}
		}else{
			foreach(current($this->data) as $c => $column){
				$colWidths[$c] = strlen($column);
			}
		}

		$cellWidths = [];
		foreach($this->data as $r => $row){
			foreach($row as $c => $column){
				// For every 'cell' calculate the amount of EXTRA characters we need to bring it to the required width
				// We don't calculate the absolute width, that was not working, we calculate the EXTRA needed, it's more reliable
				// it's hard to know how much to calculate when each cell can have different amounts of non-printing characters
				$cw = strlen($column);
				$cw = $this->adjustColumnWidthByNonPrintingChars($cw, $column);
				$cellWidths[$r][$c] = $colWidths[$c] - $cw;
			}
		}
		$this->var_dump(['widths' => $cellWidths]);

		$output = "";
		foreach($this->data as $r => $row){
			$text = $row;

			// If row has more than 1 column, we need to adjust the padding for each cell
			if(count($row) > 1) {
				$text = [];
				foreach ($row as $c => $column) {
					// pad the cell with the extra width we need
					$width = strlen($column) + $cellWidths[$r][$c] + $this->rightPadding;
					$column = str_pad($column, $width, $this->space);
					// the column has the required EXTRA padding now
					$text[] = $column;
					$this->debug("row[$r] column[$c] width($width) length(".strlen($column).") = $column");
				}
			}

			// we are done with this line now, finalise it's output somewheres
			// implode, buffer, print
			$row = implode(" ", $text) . "\n";

			if($buffer){
				$output .= $row;
			}else{
				print($row);
			}
		}

		return strlen($output) ? $output : null;
	}
}
