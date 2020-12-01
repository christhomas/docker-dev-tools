<?php
interface ConfigInterface
{
    public function setKey(string $key, $value): void;
    public function getKey(string $key);
}