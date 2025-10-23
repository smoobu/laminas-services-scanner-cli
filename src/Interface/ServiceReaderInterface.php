<?php

declare(strict_types=1);

namespace Smoobu\LaminasServiceScanner\Interface;

use Smoobu\LaminasServiceScanner\DTO\ServiceInfo;
use Smoobu\LaminasServiceScanner\DTO\HiddenDependency;

interface ServiceReaderInterface
{
    /**
     * Get all registered services
     *
     * @return ServiceInfo[]
     */
    public function getAllServices(): array;

    /**
     * Get services filtered by name pattern
     *
     * @param string|null $filter
     * @return ServiceInfo[]
     */
    public function getServices(?string $filter = null, ?string $type = null): array;

    /**
     * Get services filtered by type
     *
     * @param string|null $type
     * @return ServiceInfo[]
     */
    public function getServicesByType(?string $type = null): array;

    /**
     * Get a specific service by name
     *
     * @param string $serviceName
     * @return ServiceInfo|null
     */
    public function getService(string $serviceName): ?ServiceInfo;

    /**
     * Check if a service exists
     *
     * @param string $serviceName
     * @return bool
     */
    public function hasService(string $serviceName): bool;

    /**
     * Get service instance
     *
     * @param string $serviceName
     * @return mixed
     * @throws \Exception
     */
    public function getServiceInstance(string $serviceName): mixed;

    /**
     * Get hidden dependencies for a service
     *
     * @param string $serviceName
     * @return HiddenDependency[]
     */
    public function getHiddenDependencies(string $serviceName): array;

    /**
     * Check if service uses SR\Di pattern
     *
     * @param string $serviceName
     * @return bool
     */
    public function usesSRDi(string $serviceName): bool;
}
