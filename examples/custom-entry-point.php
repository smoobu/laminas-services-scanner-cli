<?php

declare(strict_types=1);

/**
 * Example custom entry point for integrating with your Laminas application
 * 
 * This example shows how to create a custom entry point that uses your
 * existing ServiceManager configuration instead of the demo one.
 */

use AiSupaScan\LaminasServicesCli\Application;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Config\Config;

// Include Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Example 1: Using a configuration array
$config = [
    'services' => [
        'config' => [
            'database' => [
                'host' => 'localhost',
                'port' => 3306,
                'name' => 'myapp',
            ],
            'cache' => [
                'adapter' => 'redis',
                'host' => 'localhost',
                'port' => 6379,
            ]
        ],
        'logger' => new class {
            public function info(string $message): void
            {
                echo "[INFO] $message\n";
            }
            
            public function error(string $message): void
            {
                echo "[ERROR] $message\n";
            }
        }
    ],
    'invokables' => [
        'DateTime' => DateTime::class,
        'ArrayObject' => ArrayObject::class,
        'stdClass' => stdClass::class,
    ],
    'factories' => [
        'database' => function($container) {
            $config = $container->get('config');
            $dbConfig = $config['database'];
            
            // Return a mock database connection
            return new class($dbConfig) {
                private array $config;
                
                public function __construct(array $config)
                {
                    $this->config = $config;
                }
                
                public function getConfig(): array
                {
                    return $this->config;
                }
                
                public function query(string $sql): array
                {
                    return ['result' => 'mock data'];
                }
            };
        },
        'cache' => function($container) {
            $config = $container->get('config');
            $cacheConfig = $config['cache'];
            
            // Return a mock cache adapter
            return new class($cacheConfig) {
                private array $config;
                private array $data = [];
                
                public function __construct(array $config)
                {
                    $this->config = $config;
                }
                
                public function get(string $key): mixed
                {
                    return $this->data[$key] ?? null;
                }
                
                public function set(string $key, mixed $value): void
                {
                    $this->data[$key] = $value;
                }
                
                public function getConfig(): array
                {
                    return $this->config;
                }
            };
        },
    ],
    'aliases' => [
        'db' => 'database',
        'log' => 'logger',
        'cache.adapter' => 'cache',
    ]
];

$serviceManager = new ServiceManager($config);

// Create and run the application
$application = new Application($serviceManager);
$application->run();
