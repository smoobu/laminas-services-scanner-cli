<?php

declare(strict_types=1);

namespace Smoobu\LaminasServiceScanner;

use Smoobu\LaminasServiceScanner\Command\InspectServiceCommand;
use Smoobu\LaminasServiceScanner\Command\ListServicesCommand;
use Smoobu\LaminasServiceScanner\Interface\ServiceContainerInterface;
use Smoobu\LaminasServiceScanner\Interface\ServiceReaderInterface;
use Smoobu\LaminasServiceScanner\Service\LaminasServiceContainer;
use Smoobu\LaminasServiceScanner\Service\LaminasServiceReader;
use Smoobu\LaminasServiceScanner\Service\ScanFileForHiddenDeps;
use Smoobu\LaminasServiceScanner\Service\SRDiServiceContainer;
use Smoobu\LaminasServiceScanner\Service\SRDiServiceReader;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Command\Command;

class Application extends BaseApplication
{
    public function __construct(
        private ServiceContainerInterface $container,
        private ServiceReaderInterface $serviceReader
    ) {
        parent::__construct('Laminas Services CLI', '1.0.0');
        $this->container = $container;
        $this->serviceReader = $serviceReader;
    }

    protected function getDefaultCommands(): array
    {
        $commands = parent::getDefaultCommands();
        $commands[] = new ListServicesCommand($this->serviceReader);
        $commands[] = new InspectServiceCommand($this->serviceReader);
        
        return $commands;
    }

    /**
     * Create application with Laminas ServiceManager
     */
    public static function createWithLaminasServiceManager($serviceManager): self
    {
        $container = new LaminasServiceContainer($serviceManager);
        $reader = new LaminasServiceReader($container, new ScanFileForHiddenDeps());
        return new self($container, reader);
    }

    /**
     * Create application with SR/Di container
     */
    public static function createWithSRDi($di): self
    {
        $container = new SRDiServiceContainer($di);
        $reader = new SRDiServiceReader($container, new ScanFileForHiddenDeps());
        return new self($container, $reader);
    }
}
