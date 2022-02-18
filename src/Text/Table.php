<?php declare(strict_types=1);

namespace DDT\Text;

class Table
{
    private $text;
	private $data;
	private $tabWidth = 2;
	private $numColumns = 0;
	private $numHeaderRows = 0;
	private $border = null;
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

	public function setNumHeaderRows(int $numHeaderRows): void
	{
		$this->numHeaderRows = $numHeaderRows;
	}

	public function setBorder(string $v, string $h): void
	{
		if(!empty($v) || !empty($h)){
			$this->border = ['v' => " $v ", 'h' => $h];
		}else{
			$this->border = null;
		}
	}

	private function calcPrintingWidth(string $text): int
	{
		$tw = strlen($text);
		$np = 0;

		$codes = $this->text->findCodes($text);
		foreach($codes as $c){
			$tw = $tw + $c['printing'];
			$np = $np + $c['length'];
		}
		
		return (int)($tw - $np);
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
			$accum[] = $this->calcPrintingWidth($text);
			return $accum;
		}, []);

		// The Maximum printing width, is the minimum display width for this column
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

	public function render(): ?string
	{
		if(empty($this->data)) return null;

		$data = $this->data;
		array_unshift($data, []);

		// Flip the array to process columns easily
		$data = array_map(null, ...$data);

		// Fix up all the column widths, including all hidden non-printing characters
		$data = array_map(function($columns){
			return $this->fixColumnWidths($columns);
		}, $data);

		// Flip the array back to normal
		$data = array_map(null, ...$data);
		$data = array_slice($data, 1);

		// Render the header and body
		$header	= $this->renderHeader(array_slice($data, 0, $this->numHeaderRows));
		$body	= $this->renderBody(array_slice($data, $this->numHeaderRows));

		// Combine both the header and body together, trimming off what we don't want
		return trim(implode("\n", array_merge($header, $body))) . "\n";
	}

	private function renderHeader(array $rows): array
	{
		if(count($rows) === 0) return [];

		foreach($rows as $index => $row){
			$b = $this->border ? $this->border['v'] : '';
			$rows[$index] = trim($b . implode($b, $row) . $b);
		}

		$pw = $this->calcPrintingWidth($rows[0]);
		$line = $this->border ? str_pad('', $pw, $this->border['h']) : '';
		$line = [$line];

		return array_merge($line, $rows);
	}

	private function renderBody(array $rows): array
	{
		if(count($rows) === 0) return [];

		foreach($rows as $index => $row){
			$b = $this->border ? $this->border['v'] : '';
			$rows[$index] = trim($b . implode($b, $row) . $b);
		}

		$pw = $this->calcPrintingWidth($rows[0]);
		$line = $this->border ? str_pad('', $pw, $this->border['h']) : '';
		$line = [$line];

		return array_merge($line, $rows, $line);
	}
}
