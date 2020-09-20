<?php
class ProjectConfig extends BaseConfig
{
    public function listHealthcheck(): array
	{
		// FIXME: broken because scanConfigTree was replaced and not upgraded with the ability to callback on the found information
		$list = $this->scanConfigTree("healthchecks", function($key, $value) {
			if(Healthcheck::isHealthcheck($value)){
				return [$key];
			}
		});

		return is_array($list) ? $list : [];
	}

	public function getHealthcheck(string $name): Healthcheck
	{
		// FIXME: broken because scanConfigTree was replaced and not upgraded with the ability to callback on the found information
		$data = $this->scanConfigTree("healthchecks", function($key, $value) use ($name) {
			if($key === $name && Healthcheck::isHealthcheck($value)){
				$value["name"] = $key;
				return [$key => $value];
			}
		});

		return new Healthcheck(current($data));
	}
}