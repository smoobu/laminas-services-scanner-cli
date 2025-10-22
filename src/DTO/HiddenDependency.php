<?php

declare(strict_types=1);

namespace Smoobu\LaminasServiceScanner\DTO;

class HiddenDependency
{
    public function __construct(
        public readonly string $service,
        public readonly string $file,
        public readonly int $line,
        public readonly string $context
    ) {}

    public function toArray(): array
    {
        return [
            'service' => $this->service,
            'file' => $this->file,
            'line' => $this->line,
            'context' => $this->context,
        ];
    }
}
