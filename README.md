# Laminas Services CLI

A command-line tool for inspecting and displaying all registered services in a Laminas ServiceManager container.

## Features

- List all registered services in a Laminas container
- Filter services by name pattern or type
- Inspect individual services in detail
- Show service types (service, alias, factory, invokable)
- Display reflection information for service instances
- Find aliases pointing to specific services
- **Hidden Dependencies Analysis**: Scan for SR\Di usage and find hidden dependencies via `$this->getDi()` calls

## Installation

```bash
composer require smoobu/laminas-services-scanner-cli
```

## Usage

### Basic Usage

```bash
# List all services
./bin/laminas-services services:list

# List services with detailed information
./bin/laminas-services services:list --detailed

# Filter services by name pattern
./bin/laminas-services services:list --filter="logger"

# Filter services by type
./bin/laminas-services services:list --type="alias"
```

### Inspect Specific Service

```bash
# Inspect a specific service
./bin/laminas-services services:inspect logger

# Inspect with instantiation to get more details
./bin/laminas-services services:inspect logger --instantiate
```

## Integration with Your Application

To use this tool with your existing Laminas application, you need to create a custom entry point that uses your configured ServiceManager:

```php
#!/usr/bin/env php
<?php

use Smoobu\LaminasServiceScanner\Application;
use YourApp\ServiceManagerFactory;

// Include your application's autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Get your configured ServiceManager
$serviceManager = ServiceManagerFactory::create();

// Create and run the application
$application = new Application($serviceManager);
$application->run();
```

## Commands

### `services:list`

Lists all registered services in the container.

**Options:**
- `--filter, -f`: Filter services by name pattern
- `--type, -t`: Filter services by type (service, alias, factory, invokable)
- `--detailed, -d`: Show detailed information about each service

**Examples:**
```bash
./bin/laminas-services services:list
./bin/laminas-services services:list --filter="db"
./bin/laminas-services services:list --type="alias" --detailed
```

### `services:inspect`

Inspects a specific service in detail.

**Arguments:**
- `service`: The service name to inspect

**Options:**
- `--instantiate, -i`: Try to instantiate the service to get more details
- `--show-hidden-deps, -s`: Scan for hidden dependencies using SR\Di (AbstractDi/DiTrait)

**Examples:**
```bash
./bin/laminas-services services:inspect logger
./bin/laminas-services services:inspect db --instantiate
./bin/laminas-services services:inspect my-service --show-hidden-deps
```

## Service Types

The tool recognizes the following service types:

- **service**: Regular service instances
- **alias**: Service aliases pointing to other services
- **factory**: Services created by factory classes
- **invokable**: Services created by instantiating a class directly

## Hidden Dependencies Analysis

The `--show-hidden-deps` option scans services for hidden dependencies using the SR\Di pattern. This feature:

1. **Detects SR\Di Usage**: Checks if a service extends `SR\Di\AbstractDi` or uses `SR\Di\DiTrait`
2. **Scans Source Code**: Analyzes the service's source code and all parent classes for `$this->getDi()` method calls
3. **Reports Dependencies**: Shows which services are being accessed through the DI container within the service code
4. **Provides Context**: Displays the file, line number, and surrounding code context for each hidden dependency

This is particularly useful for:
- Understanding service dependencies that aren't declared in the ServiceManager configuration
- Identifying services that use the SR\Di pattern for dependency injection
- Debugging complex service relationships
- Refactoring services to use explicit dependency injection

## Output Examples

### Simple List
```
Registered Services
==================
+-------------+--------+------------------+
| Service Name| Type   | Class/Value      |
+-------------+--------+------------------+
| config      | service| array            |
| logger      | service| class@anonymous  |
| DateTime    | invokable| DateTime        |
| log         | alias  | logger           |
+-------------+--------+------------------+
```

### Detailed Inspection
```
Service: logger
===============

Name:     logger
Type:     Service
Shared:   Yes
Factory:  class@anonymous

Service Instance
================

Class:    class@anonymous
Methods:  log

Reflection Information
=====================

Namespace:    
Short Name:   class@anonymous
Is Abstract:  No
Is Interface: No
Is Trait:     No
```

### Hidden Dependencies Analysis
```
Hidden Dependencies Analysis
===========================

⚠ Found 2 hidden dependency(ies):

Service:  logger
File:     /path/to/MyService.php
Line:     45
Context:  ...$this->getDi('logger')->info('Processing data')...

Service:  database
File:     /path/to/MyService.php
Line:     78
Context:  ...$db = $this->getDi('database');...
```

## Requirements

- PHP 8.1 or higher
- Laminas ServiceManager 3.0 or higher
- Symfony Console 6.0 or higher

## License

MIT License

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests
5. Submit a pull request

## Support

For issues and questions, please use the GitHub issue tracker.
