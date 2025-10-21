<?php

declare(strict_types=1);

/**
 * Example integration with Laminas MVC application
 * 
 * This example shows how to integrate with a full Laminas MVC application
 * by using the application's ServiceManager.
 */

use Smoobu\LaminasServiceScanner\Application;
use Laminas\Mvc\Application as MvcApplication;

// Include your Laminas MVC application's bootstrap
require_once __DIR__ . '/../vendor/autoload.php';

try {
    // Initialize your Laminas MVC application
    $appConfig = require __DIR__ . '/../config/application.config.php';
    $mvcApplication = MvcApplication::init($appConfig);
    
    // Get the ServiceManager from the MVC application
    $serviceManager = $mvcApplication->getServiceManager();
    
    // Create and run the CLI application
    $cliApplication = new Application($serviceManager);
    $cliApplication->run();
    
} catch (\Exception $e) {
    echo "Error initializing application: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
