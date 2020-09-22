<?php
class ExtensionConfig extends BaseConfig
{
    const FILENAME = "ddt-extension.json";

    public function __construct(string $path)
    {
        switch(true){
            case file_exists($path):
                $filename = $path;
            break;

            case file_exists($path . '/' . self::FILENAME):
                $filename = $path . '/' . self::FILENAME;
            break;

            default:
                throw new ConfigMissingException($path);
            break;
        }

        parent::__construct($filename);

        if($this->getType() !== 'extension'){
            throw new ConfigWrongTypeException([$this->getType(), 'extension']);
        }
    }
}