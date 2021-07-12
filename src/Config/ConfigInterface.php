<?php declare(strict_types=1);

namespace DDT\Config;
interface ConfigInterface
{
    public function getDefaultFilename(): string;
    public function setKey(string $key, $value): void;
    public function getKey(string $key);
}