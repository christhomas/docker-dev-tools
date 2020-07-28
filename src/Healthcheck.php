<?php
class Healthcheck
{
	private $data;

	static public function isHealthcheck($data): bool
	{
		if(!is_array($data)) return false;

		if(in_array(false, [
			ArrayWrapper::has($data, 'type'),
			ArrayWrapper::has($data, 'host'),
			ArrayWrapper::has($data, 'port')
		])){
			return false;
		}

		$test = ArrayWrapper::get($data, 'test');
		if($test === null) return true;

		if($test === 'find_text'){
			if(in_array(false, [
				ArrayWrapper::has($data, 'path'),
				ArrayWrapper::has($data, 'text'),
			])){
				return false;
			}
		}

		if($test === 'find_json'){
			if(in_array(false, [
				ArrayWrapper::has($data, 'path'),
				ArrayWrapper::has($data, 'json'),
			])){
				return false;
			}
		}

		if($test === 'find_database'){
			if(in_array(false, [
				ArrayWrapper::has($data, 'db_user'),
				ArrayWrapper::has($data, 'db_user'),
				ArrayWrapper::has($data, 'db_list')
			])){
				return false;
			}
		}
		return true;
	}

	public function __construct(array $data)
	{
		if(!self::isHealthcheck($data)){
			throw new RuntimeException("The data provided was not a valid healthcheck");
		}

		$this->data = $data;
	}

	public function run(): ?array
	{
		$result = [
			"url" => "{$this->data['host']}:{$this->data['port']}",
			"connected" => $this->testConnectivity($this->data['host'], $this->data['port']),
		];

		switch(ArrayWrapper::get($this->data, 'test')){
			case 'find_text':
				$result = array_merge($result, $this->testFindText(
					$this->data['host'],
					$this->data['port'],
					$this->data['path'],
					$this->data['text'],
				));
				break;

			case 'find_json':
				$result = array_merge($result, $this->testFindJson(
					$this->data['host'],
					$this->data['port'],
					$this->data['path'],
					$this->data['json'],
				));
				break;

			case 'find_database':
				$result = array_merge($result, $this->testFindDatabase(
					$this->data['host'],
					$this->data['port'],
					$this->data['db_user'],
					$this->data['db_password'],
					$this->data['db_list']
				));
				break;
		}

		return $result;
	}

	private function testConnectivity($host, $port): bool
	{
		$host = str_replace(["https://","http://"],"",$host);

		$fp = @fsockopen($u="tcp://$host:$port");

		$status = !!$fp;

		if($fp) fclose($fp);

		return $status;
	}

	private function testFindText($host, $port, $path, $text): array
	{
		$result = $this->getHTTPResponse($host, $port, $path);
		$result['success'] = false;

		if($result['have_body']){
			if(is_string($text) && !empty($text)){
				$result['success'] = strpos($result['body'], $text) !== false;
			}
		}

		return $result;
	}

	private function testFindJson($host, $port, $path, $json): array
	{
		$result = $this->getHTTPResponse($host, $port, $path);
		$result['success'] = false;

		if($result['have_body']){
			$body = json_decode($result['body'], true);

			function json_search($src, $dst){
				if(!is_array($src) || !is_array($dst)) return false;

				foreach($dst as $key => $value){
					if(!array_key_exists($key, $src)) return false;
					if(is_scalar($value) && $src[$key] !== $value) return false;
					if(!is_scalar($value)) return json_search($src[$key], $value);
				}

				return true;
			}
			$result['success'] = json_search($body, $json);
		}

		return $result;
	}

	private function testFindDatabase($host, $port, $username, $password, $databases): array
	{
		$result = ['success' => false];

		if (empty($databases)) return $result;

		$connected = [];

		foreach ($databases as $db) {
			$connected[$db] = $this->connectDatabase($host, $port, $username, $password, $db);
		}

		$result['url'] = "{$host}:{$port}";
		$result['success'] = !in_array(false, $connected);
		$result['db_status'] = $connected;

		return $result;
	}

	private function connectDatabase($host, $port, $username, $password, $database): bool
	{
		$dsn = "mysql:host=$host;port=$port;dbname=$database;charset=utf8";

		$options = [
			PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
			PDO::ATTR_EMULATE_PREPARES   => false,
		];

		try {
			$connection = new PDO($dsn, $username, $password, $options);

			return !!$connection;
		} catch (PDOException $e) {
			return false;
		}
	}

	private function getHTTPResponse($host, $port, $path)
	{
		$curl = new Curl();
		return $curl->get($host, $port, $path);
	}
}
