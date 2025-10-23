<?php

declare(strict_types=1);

namespace Smoobu\LaminasServiceScanner\Service;

use Smoobu\LaminasServiceScanner\Interface\ServiceContainerInterface;
use SR\Di\Di;

class SRDiServiceContainer implements ServiceContainerInterface
{
    public function __construct(
        private Di $di
    ) {}

    public function has(string $serviceName): bool
    {
        $filteredName = $this->filterClassName($serviceName);
        return array_key_exists($filteredName, $this->getRegisteredObjects()) || 
               array_key_exists($filteredName, $this->getAliases());
    }

    public function get(string $serviceName): mixed
    {
        return $this->di->get($serviceName);
    }

    public function getRegisteredServices(): array
    {
        $services = array_keys($this->getRegisteredObjects());
        $aliases = array_keys($this->getAliases());
        return array_unique(array_merge($services, $aliases));
    }

    public function isShared(string $serviceName): bool
    {
        $filteredName = $this->filterClassName($serviceName);
        $registeredObjects = $this->getRegisteredObjects();
        
        if (array_key_exists($filteredName, $registeredObjects)) {
            return $registeredObjects[$filteredName]['shared'] ?? false;
        }
        
        return false;
    }

    public function hasAlias(string $serviceName): bool
    {
        $filteredName = $this->filterClassName($serviceName);
        return array_key_exists($filteredName, $this->getAliases());
    }

    public function getAlias(string $serviceName): string
    {
        $filteredName = $this->filterClassName($serviceName);
        $aliases = $this->getAliases();
        return $aliases[$filteredName] ?? $serviceName;
    }

    public function hasFactory(string $serviceName): bool
    {
        $filteredName = $this->filterClassName($serviceName);
        $registeredObjects = $this->getRegisteredObjects();
        return array_key_exists($filteredName, $registeredObjects);
    }

    public function getFactory(string $serviceName): mixed
    {
        $filteredName = $this->filterClassName($serviceName);
        $registeredObjects = $this->getRegisteredObjects();
        
        if (array_key_exists($filteredName, $registeredObjects)) {
            return $registeredObjects[$filteredName]['closure'] ?? null;
        }
        
        return null;
    }

    public function hasInvokableClass(string $serviceName): bool
    {
        // SR/Di doesn't have invokable classes like Laminas
        return false;
    }

    public function getInvokableClass(string $serviceName): string
    {
        // SR/Di doesn't have invokable classes like Laminas
        return '';
    }

    /**
     * Get registered objects using reflection
     */
    private function getRegisteredObjects(): array
    {
        $reflection = new \ReflectionClass($this->di);
        $property = $reflection->getProperty('aRegisteredObjects');
        $property->setAccessible(true);
        return $property->getValue($this->di);
    }

    /**
     * Get aliases using reflection
     */
    private function getAliases(): array
    {
        $reflection = new \ReflectionClass($this->di);
        $property = $reflection->getProperty('aAliases');
        $property->setAccessible(true);
        return $property->getValue($this->di);
    }

    /**
     * Filter class name (same logic as SR/Di)
     */
    private function filterClassName(string $className): string
    {
        return str_replace('\\', '_', $className);
    }
}
