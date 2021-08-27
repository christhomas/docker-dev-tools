<?php declare(strict_types=1);

spl_autoload_register(function ($fqcn) {
    // namespace autoloader
    $class = implode('/', array_slice(explode('\\', $fqcn), 1));

    $file = __DIR__ . '/' . $class . '.php';

    if (strlen($class) && file_exists($file)) {
        return require_once($file);
    }

    // old autoloader (deprecated)
    $search = [
        __DIR__ . '/',
        __DIR__ . '/Exceptions/',
        __DIR__ . '/Config/',
    ];

    foreach($search as $base){
        $file = $base . $fqcn . '.php';

        if(file_exists($file)){
            return require_once($file);
        }
    }
});

function container(?string $ref = null, ?array $args = [])
{
    static $container = null;

    if($container === null){
        $container = \DDT\Container::$instance ?? new \DDT\Container();
    }

    if(is_string($ref)) {
        return $container->get($ref, $args);
    }

    return $container;
}