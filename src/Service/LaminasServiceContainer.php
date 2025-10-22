<?php

declare(strict_types=1);

namespace Smoobu\LaminasServiceScanner\Service;

use Smoobu\LaminasServiceScanner\Interface\ServiceContainerInterface;
use Laminas\ServiceManager\ServiceManager;

class LaminasServiceContainer implements ServiceContainerInterface
{
    public function __construct(
        private ServiceManager $serviceManager
    ) {}

    public function has(string $serviceName): bool
    {
        return $this->serviceManager->has($serviceName);
    }

    public function get(string $serviceName): mixed
    {
        return $this->serviceManager->get($serviceName);
    }

    public function getRegisteredServices(): array
    {
        return $this->serviceManager->getRegisteredServices();
    }

    public function isShared(string $serviceName): bool
    {
        return $this->serviceManager->isShared($serviceName);
    }

    public function hasAlias(string $serviceName): bool
    {
        return $this->serviceManager->hasAlias($serviceName);
    }

    public function getAlias(string $serviceName): string
    {
        return $this->serviceManager->getAlias($serviceName);
    }

    public function hasFactory(string $serviceName): bool
    {
        return $this->serviceManager->hasFactory($serviceName);
    }

    public function getFactory(string $serviceName): mixed
    {
        return $this->serviceManager->getFactory($serviceName);
    }

    public function hasInvokableClass(string $serviceName): bool
    {
        return $this->serviceManager->hasInvokableClass($serviceName);
    }

    public function getInvokableClass(string $serviceName): string
    {
        return $this->serviceManager->getInvokableClass($serviceName);
    }
}
