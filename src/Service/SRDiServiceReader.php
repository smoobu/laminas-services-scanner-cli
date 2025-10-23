<?php

declare(strict_types=1);

namespace Smoobu\LaminasServiceScanner\Service;

use Smoobu\LaminasServiceScanner\DTO\ServiceInfo;
use Smoobu\LaminasServiceScanner\DTO\HiddenDependency;
use Smoobu\LaminasServiceScanner\Interface\ServiceReaderInterface;
use Smoobu\LaminasServiceScanner\Interface\ServiceContainerInterface;

class SRDiServiceReader implements ServiceReaderInterface
{
    public function __construct(
        private ServiceContainerInterface $container
    ) {}

    public function getAllServices(): array
    {
        return $this->getServices();
    }

    public function getServices(?string $filter = null, ?string $type = null): array
    {
        $services = [];
        $registeredServices = $this->container->getRegisteredServices();

        foreach ($registeredServices as $serviceName) {
            // Apply filter if provided
            if ($filter && !str_contains(strtolower($serviceName), strtolower($filter))) {
                continue;
            }

            try {
                $serviceInfo = $this->getServiceInfo($serviceName);
                
                // Apply type filter if provided
                if ($type && $serviceInfo->type !== $type) {
                    continue;
                }
                
                $services[$serviceName] = $serviceInfo;
            } catch (\Exception $e) {
                // Skip services that can't be inspected
                $services[$serviceName] = new ServiceInfo(
                    name: $serviceName,
                    type: 'unknown',
                    class: 'Error: ' . $e->getMessage(),
                    error: $e->getMessage()
                );
            }
        }

        ksort($services);
        return $services;
    }

    public function getServicesByType(?string $type = null): array
    {
        return $this->getServices(null, $type);
    }

    public function getService(string $serviceName): ?ServiceInfo
    {
        if (!$this->container->has($serviceName)) {
            return null;
        }

        try {
            return $this->getServiceInfo($serviceName);
        } catch (\Exception $e) {
            return new ServiceInfo(
                name: $serviceName,
                type: 'unknown',
                class: 'Error: ' . $e->getMessage(),
                error: $e->getMessage()
            );
        }
    }

    public function hasService(string $serviceName): bool
    {
        return $this->container->has($serviceName);
    }

    public function getServiceInstance(string $serviceName): mixed
    {
        return $this->container->get($serviceName);
    }

    public function getHiddenDependencies(string $serviceName): array
    {
        $hiddenDeps = [];

        try {
            $service = $this->container->get($serviceName);
            
            if (!is_object($service)) {
                return $hiddenDeps;
            }

            $reflection = new \ReflectionClass($service);
            
            if (!$this->usesSRDi($serviceName)) {
                return $hiddenDeps;
            }

            // Analyze current class and all parent classes
            $classesToAnalyze = [$reflection];
            
            // Add parent classes
            $parent = $reflection->getParentClass();
            while ($parent) {
                $classesToAnalyze[] = $parent;
                $parent = $parent->getParentClass();
            }

            foreach ($classesToAnalyze as $class) {
                $filePath = $class->getFileName();
                if (!$filePath || !file_exists($filePath)) {
                    continue;
                }

                $deps = $this->scanFileForHiddenDeps($filePath, $class->getName());
                $hiddenDeps = array_merge($hiddenDeps, $deps);
            }

        } catch (\Exception $e) {
            // Return empty array on error
        }

        return $hiddenDeps;
    }

    public function usesSRDi(string $serviceName): bool
    {
        try {
            $service = $this->container->get($serviceName);
            
            if (!is_object($service)) {
                return false;
            }

            $reflection = new \ReflectionClass($service);
            
            // Check if extends SR\Di\AbstractDi
            $parent = $reflection;
            while ($parent) {
                if ($parent->getName() === 'SR\Di\AbstractDi') {
                    return true;
                }
                $parent = $parent->getParentClass();
            }

            // Check if uses SR\Di\DiTrait
            $traits = $this->getAllTraits($reflection);
            foreach ($traits as $trait) {
                if ($trait->getName() === 'SR\Di\DiTrait') {
                    return true;
                }
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function getServiceInfo(string $serviceName): ServiceInfo
    {
        $type = 'service';
        $class = 'unknown';
        $isShared = false;
        $isAliased = false;
        $aliases = [];
        $factory = null;

        // Check if it's an alias
        if ($this->container->hasAlias($serviceName)) {
            $type = 'alias';
            $isAliased = true;
            $class = $this->container->getAlias($serviceName);
            return new ServiceInfo(
                name: $serviceName,
                type: $type,
                class: $class,
                isShared: $isShared,
                isAliased: $isAliased,
                aliases: $aliases
            );
        }

        // Check if it's a service with factory
        if ($this->container->hasFactory($serviceName)) {
            $type = 'service';
            $factoryObj = $this->container->getFactory($serviceName);
            $factory = is_object($factoryObj) ? 'Closure' : (string) $factoryObj;
        }

        // Check if it's a service
        if ($this->container->has($serviceName)) {
            try {
                $service = $this->container->get($serviceName);
                if (is_object($service)) {
                    $class = get_class($service);
                } else {
                    $class = gettype($service);
                }
            } catch (\Exception $e) {
                $class = 'Error: ' . $e->getMessage();
            }
        }

        // Check if it's shared
        $isShared = $this->container->isShared($serviceName);

        return new ServiceInfo(
            name: $serviceName,
            type: $type,
            class: $class,
            isShared: $isShared,
            isAliased: $isAliased,
            aliases: $aliases,
            factory: $factory
        );
    }

    private function getAllTraits(\ReflectionClass $reflection): array
    {
        $traits = [];
        
        // Get traits from current class
        $traits = array_merge($traits, $reflection->getTraits());
        
        // Get traits from parent classes
        $parent = $reflection->getParentClass();
        while ($parent) {
            $traits = array_merge($traits, $parent->getTraits());
            $parent = $parent->getParentClass();
        }

        return $traits;
    }

    private function scanFileForHiddenDeps(string $filePath, string $className): array
    {
        $hiddenDeps = [];
        $content = file_get_contents($filePath);
        
        if (!$content) {
            return $hiddenDeps;
        }

        $lines = explode("\n", $content);
        
        // Pattern to match $this->getDi() calls
        $pattern = '/\$this\s*->\s*getDi\s*\(\s*[\'"]([^\'"]+)[\'"]?\s*\)/';
        
        foreach ($lines as $lineNumber => $line) {
            if (preg_match_all($pattern, $line, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[1] as $match) {
                    $serviceName = $match[0];
                    $offset = $match[1];
                    
                    // Get context around the match
                    $context = $this->getContextAroundMatch($line, $offset);
                    
                    $hiddenDeps[] = new HiddenDependency(
                        service: $serviceName,
                        file: $filePath,
                        line: $lineNumber + 1,
                        context: $context
                    );
                }
            }
        }

        return $hiddenDeps;
    }

    private function getContextAroundMatch(string $line, int $offset, int $contextLength = 50): string
    {
        $start = max(0, $offset - $contextLength);
        $end = min(strlen($line), $offset + $contextLength);
        
        $context = substr($line, $start, $end - $start);
        
        // Add ellipsis if we truncated
        if ($start > 0) {
            $context = '...' . $context;
        }
        if ($end < strlen($line)) {
            $context = $context . '...';
        }
        
        return trim($context);
    }
}
