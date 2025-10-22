<?php

declare(strict_types=1);

namespace Smoobu\LaminasServiceScanner;

use Smoobu\LaminasServiceScanner\Interface\ServiceContainerInterface;
use Smoobu\LaminasServiceScanner\Interface\ServiceReaderInterface;
use Smoobu\LaminasServiceScanner\Service\LaminasServiceContainer;
use Smoobu\LaminasServiceScanner\Service\LaminasServiceReader;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Command\Command;

class Application extends BaseApplication
{
    private ServiceContainerInterface $container;
    private ServiceReaderInterface $serviceReader;

    public function __construct(ServiceContainerInterface $container, ?ServiceReaderInterface $serviceReader = null)
    {
        parent::__construct('Laminas Services CLI', '1.0.0');
        $this->container = $container;
        $this->serviceReader = $serviceReader ?? new LaminasServiceReader($container);
    }

    public function getContainer(): ServiceContainerInterface
    {
        return $this->container;
    }

    public function getServiceReader(): ServiceReaderInterface
    {
        return $this->serviceReader;
    }

    protected function getDefaultCommands(): array
    {
        $commands = parent::getDefaultCommands();
        $commands[] = new Command\ListServicesCommand($this->serviceReader);
        $commands[] = new Command\InspectServiceCommand($this->serviceReader);
        
        return $commands;
    }

    /**
     * Create application with Laminas ServiceManager
     */
    public static function createWithLaminasServiceManager($serviceManager): self
    {
        $container = new LaminasServiceContainer($serviceManager);
        return new self($container);
    }
}
