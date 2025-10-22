<?php

declare(strict_types=1);

namespace Smoobu\LaminasServiceScanner\Interface;

interface ServiceContainerInterface
{
    /**
     * Check if a service exists in the container
     *
     * @param string $serviceName
     * @return bool
     */
    public function has(string $serviceName): bool;

    /**
     * Get a service instance from the container
     *
     * @param string $serviceName
     * @return mixed
     * @throws \Exception
     */
    public function get(string $serviceName): mixed;

    /**
     * Get all registered service names
     *
     * @return string[]
     */
    public function getRegisteredServices(): array;

    /**
     * Check if a service is shared
     *
     * @param string $serviceName
     * @return bool
     */
    public function isShared(string $serviceName): bool;

    /**
     * Check if a service is an alias
     *
     * @param string $serviceName
     * @return bool
     */
    public function hasAlias(string $serviceName): bool;

    /**
     * Get alias target
     *
     * @param string $serviceName
     * @return string
     */
    public function getAlias(string $serviceName): string;

    /**
     * Check if a service has a factory
     *
     * @param string $serviceName
     * @return bool
     */
    public function hasFactory(string $serviceName): bool;

    /**
     * Get service factory
     *
     * @param string $serviceName
     * @return mixed
     */
    public function getFactory(string $serviceName): mixed;

    /**
     * Check if a service is invokable
     *
     * @param string $serviceName
     * @return bool
     */
    public function hasInvokableClass(string $serviceName): bool;

    /**
     * Get invokable class
     *
     * @param string $serviceName
     * @return string
     */
    public function getInvokableClass(string $serviceName): string;
}
