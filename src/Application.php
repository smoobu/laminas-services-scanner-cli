<?php

declare(strict_types=1);

namespace Smoobu\LaminasServiceScanner;

use Laminas\ServiceManager\ServiceManager;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Command\Command;

class Application extends BaseApplication
{
    private ServiceManager $serviceManager;

    public function __construct(ServiceManager $serviceManager)
    {
        parent::__construct('Laminas Services CLI', '1.0.0');
        $this->serviceManager = $serviceManager;
    }

    public function getServiceManager(): ServiceManager
    {
        return $this->serviceManager;
    }

    protected function getDefaultCommands(): array
    {
        $commands = parent::getDefaultCommands();
        $commands[] = new Command\ListServicesCommand();
        $commands[] = new Command\InspectServiceCommand();
        
        return $commands;
    }
}
