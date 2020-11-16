<?php
class Config extends SystemConfig
{
	// FIXME: Broken AWS Functionality
	// public function getAwsVaultProfile(): ?string
	// {
	// 	$result = $this->getKey("aws_vault");

	// 	return count($result) ? current($result) : null;
	// }

	// FIXME: Broken AWS Functionality
	// public function getBastionHost($name): array
	// {
	// 	$result = $this->getKey("bastion.hosts.$name");

	// 	$config = current($result);

	// 	if($config === null) throw new Exception("Bastion Host '$name' was not configured");

	// 	if(ArrayWrapper::get($config, 'type') === 'aws-ssm'){
	// 		list ($config["host"],$config["port"]) = Aws::getParam([$config["host"],$config["port"]]);
	// 	}

	// 	return $config;
	// }

	// FIXME: Broken AWS Functionality
	// public function getBastionService($name): array
	// {
	// 	$result = $this->scanConfigTree("bastion.services.$name");

	// 	$config = current($result);

	// 	if($config === null) throw new Exception("Bastion Service '$name' was not configured");

	// 	if(ArrayWrapper::get($config, 'type') === 'aws-ssm'){
	// 		list ($config["host"],$config["port"]) = Aws::getParam([$config["host"],$config["port"]]);

	// 		return $config;
	// 	}

	// 	return $config;
	// }
}
