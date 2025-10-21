<?php

declare(strict_types=1);

/**
 * Example Laminas MVC application configuration
 * 
 * This is a sample configuration file that would typically be used
 * in a Laminas MVC application.
 */

return [
    'modules' => [
        'Laminas\Router',
        'Laminas\Validator',
        'Laminas\Db',
        'Laminas\Log',
        'Laminas\Cache',
    ],
    'module_listener_options' => [
        'module_paths' => [
            './module',
            './vendor',
        ],
        'config_glob_paths' => [
            'config/autoload/{,*.}{global,local}.php',
        ],
        'config_cache_enabled' => false,
        'config_cache_key' => 'application.config.cache',
        'module_map_cache_enabled' => false,
        'module_map_cache_key' => 'application.module.cache',
        'cache_dir' => 'data/cache/',
        'check_dependencies' => true,
    ],
    'service_manager' => [
        'services' => [
            'config' => [
                'database' => [
                    'driver' => 'Pdo_Mysql',
                    'host' => 'localhost',
                    'port' => 3306,
                    'database' => 'myapp',
                    'username' => 'user',
                    'password' => 'password',
                    'charset' => 'utf8',
                ],
                'cache' => [
                    'adapter' => [
                        'name' => 'redis',
                        'options' => [
                            'server' => [
                                'host' => 'localhost',
                                'port' => 6379,
                            ],
                        ],
                    ],
                ],
                'log' => [
                    'writers' => [
                        [
                            'name' => 'stream',
                            'options' => [
                                'stream' => 'data/logs/application.log',
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'invokables' => [
            'DateTime' => DateTime::class,
            'ArrayObject' => ArrayObject::class,
        ],
        'factories' => [
            'Laminas\Db\Adapter\Adapter' => 'Laminas\Db\Adapter\AdapterServiceFactory',
            'Laminas\Log\Logger' => 'Laminas\Log\LoggerServiceFactory',
            'Laminas\Cache\Storage\Adapter\Redis' => 'Laminas\Cache\Storage\Adapter\RedisFactory',
        ],
        'aliases' => [
            'db' => 'Laminas\Db\Adapter\Adapter',
            'logger' => 'Laminas\Log\Logger',
            'cache' => 'Laminas\Cache\Storage\Adapter\Redis',
        ],
    ],
    'view_manager' => [
        'display_not_found_reason' => true,
        'display_exceptions' => true,
        'doctype' => 'HTML5',
        'not_found_template' => 'error/404',
        'exception_template' => 'error/index',
        'template_map' => [
            'layout/layout' => __DIR__ . '/../view/layout/layout.phtml',
            'application/index/index' => __DIR__ . '/../view/application/index/index.phtml',
            'error/404' => __DIR__ . '/../view/error/404.phtml',
            'error/index' => __DIR__ . '/../view/error/index.phtml',
        ],
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
    'router' => [
        'routes' => [
            'home' => [
                'type' => 'Laminas\Router\Http\Literal',
                'options' => [
                    'route' => '/',
                    'defaults' => [
                        'controller' => 'Application\Controller\Index',
                        'action' => 'index',
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'invokables' => [
            'Application\Controller\Index' => 'Application\Controller\IndexController',
        ],
    ],
];
