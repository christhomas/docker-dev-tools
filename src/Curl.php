<?php
class Curl
{
	public function __construct()
	{

	}

	public function get(string $host, int $port, string $path, array $headers = []): array
	{
		return $this->request("GET", $host, $port, $path, $headers);
	}

	public function post(string $host, int $port, string $path, array $headers = []): array
	{
		return $this->request("POST", $host, $port, $path, $headers);
	}

	public function request(string $method, string $host, int $port, string $path, array $headers = []): array
	{
		$url = "{$host}:{$port}{$path}";

		$ch = curl_init($url);

		// we want headers
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_TIMEOUT,30);

		if($method === "POST"){
			curl_setopt($ch, CURLOPT_POST, 1);
		}

		if(!empty($headers)){
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		}

		$response = curl_exec($ch);

		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		$header_size	= curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$headers		= substr($response, 0, $header_size);
		$body			= substr($response, $header_size);

		$error = curl_errno($ch);

		$reasons = [];

		// debugging few errors:
		//$http_code = 0;
		//$response = "$response vendor/autoload.php";

		// debugging lots of errors:
		//$http_code = 0;
		//$error = 6;
		//$response = "$response SQLSTATE PDO::__construct() errno=32 Broken pipe MySQL server has gone away symfony vendor/autoload.php";

		if($http_code === 0 && $error !== 0){
			$reasons = $reasons + $this->processCurlError($error, $url);
		}

		if($http_code !== 200){
			$reasons = $reasons + $this->processResponseErrors($response);
		}

		curl_close($ch);

		return [
			'url'			=> $url,
			'headers'		=> $headers,
			'body'			=> $body,
			'reasons'		=> $reasons,
			'http_code'		=> $http_code,
			'have_body'		=> is_string($body) && !empty($body),
			'have_headers'	=> is_string($headers) && !empty($headers),
		];
	}

	private function processResponseErrors($response): array
	{
		$reason = [];

		if(strpos($response, "SQLSTATE") !== false){
			if(strpos($response, "Access denied for user") !== false){
				$reason[] = Text::red("Reason: ") . "Database error";
				$reason[] = Text::yellow("Suggestion: ") . "Run a migration tool?";
			}
		}

		if(strpos($response, "PDO::__construct()") !== false){
			if(strpos($response, "errno=32 Broken pipe") !== false){
				$reason[] = Text::red("Reason: ") . "Database error";
				$reason[] = Text::red("Description: ") . "There is an unrecognised database error, just that pdo construct failed and it's unclear why";
				$reason[] = Text::yellow("Suggestion: ") . "Maybe restarting database containers will help";
				$reason[] = Text::yellow("Suggestion: ") . "Maybe running a migration tool to rebuild databases will help";
			}

			if(strpos($response, "MySQL server has gone away") !== false){
				$reason[] = Text::red("Reason: ") . "MySQL server has gone away";
				$reason[] = Text::yellow("Suggestion: ") . "Maybe restarting database containers will help";
			}
		}

		if(strpos($response, "symfony") !== false){
			$reason[] = Text::red("Reason: ") . "Generic Symfony Error";
			$reason[] = Text::yellow("Suggestion: ") . "Nothing to suggest, see the source code or error logs";
		}

		if(strpos($response, "vendor/autoload.php") !== false){
			$reason[] = Text::red("Reason: ") . "Autoload failed";
			$reason[] = Text::yellow("Suggestion: ") . "Run php composer to install missing libraries and generate autoloaders";
		}

		return $reason;
	}

	private function processCurlError($curl_code, $url): array
	{
		$reason = [];

		switch($curl_code){
			case 6:
				$reason[] = Text::red("Reason: ") . "Curl could not resolve endpoint '$url' (code:$curl_code)";
				break;
			case 7:
				$reason[] = Text::red("Reason: ") . "Curl refused to connect (code:$curl_code)";
				break;

			case 28:
				$reason[] = Text::red("Reason: ") . "Curl timeout (code:$curl_code)";
				break;

			default:
				$reason[] = Text::red("Reason: ") . "Unknown curl error '" . Text::yellow($curl_code) . "'";
				break;
		}

		return $reason;
	}
}
