<?php declare(strict_types=1);

namespace DDT\Config;
class ExtensionConfig extends BaseConfig
{
    public function __construct(string $path)
    {
        parent::__construct($path);

        if($this->getType() !== 'extension'){
            throw new \DDT\Exceptions\Config\ConfigWrongTypeException([$this->getType(), 'extension']);
        }
    }

    public function getDefaultFilename(): string
    {
        return 'ddt-extension.json';
    }
}