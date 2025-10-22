<?php

declare(strict_types=1);

namespace Smoobu\LaminasServiceScanner\DTO;

class ServiceInfo
{
    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly string $class,
        public readonly bool $isShared = false,
        public readonly bool $isAliased = false,
        public readonly array $aliases = [],
        public readonly ?string $factory = null,
        public readonly ?string $invokableClass = null,
        public readonly ?string $error = null
    ) {}

    public function isService(): bool
    {
        return $this->type === 'service';
    }

    public function isAlias(): bool
    {
        return $this->type === 'alias';
    }

    public function isFactory(): bool
    {
        return $this->type === 'factory';
    }

    public function isInvokable(): bool
    {
        return $this->type === 'invokable';
    }

    public function hasError(): bool
    {
        return $this->error !== null;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'class' => $this->class,
            'is_shared' => $this->isShared,
            'is_aliased' => $this->isAliased,
            'aliases' => $this->aliases,
            'factory' => $this->factory,
            'invokable_class' => $this->invokableClass,
            'error' => $this->error,
        ];
    }
}
