<?php

declare(strict_types=1);

/**
 * Example integration with SR/Di container
 * 
 * This example shows how to integrate with an SR/Di container
 * and scan its services.
 */

use Smoobu\LaminasServiceScanner\Application;
use SR\Di\Di;

// Include Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Create an SR/Di container
$di = new Di();

// Register some services
$di->register('MyApp\Logger', function($di) {
    return new class {
        public function log(string $message): void
        {
            echo "LOG: $message\n";
        }
    };
}, true); // shared instance

$di->register('MyApp\Database', function($di) {
    return new class {
        public function query(string $sql): array
        {
            return ['result' => 'mock data'];
        }
    };
}, false); // not shared

$di->register('MyApp\Cache', function($di) {
    return new class {
        private array $data = [];
        
        public function get(string $key): mixed
        {
            return $this->data[$key] ?? null;
        }
        
        public function set(string $key, mixed $value): void
        {
            $this->data[$key] = $value;
        }
    };
}, true, ['cache', 'cache.adapter']); // shared with aliases

// Register a service that uses SR\Di\AbstractDi
$di->register('MyApp\ServiceWithDi', function($di) {
    return new class extends \SR\Di\AbstractDi {
        public function doSomething(): string
        {
            // This will be detected as a hidden dependency
            $logger = $this->getDi()->get('MyApp\Logger');
            $logger->log('Doing something...');
            return 'done';
        }
    };
}, true);

// Create and run the CLI application
$application = Application::createWithSRDi($di);
$application->run();
