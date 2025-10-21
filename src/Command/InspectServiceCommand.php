<?php

declare(strict_types=1);

namespace AiSupaScan\LaminasServicesCli\Command;

use Laminas\ServiceManager\ServiceManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'services:inspect',
    description: 'Inspect a specific service in detail'
)]
class InspectServiceCommand extends Command
{
    public function __construct(
        private ServiceManager $serviceManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('service', InputArgument::REQUIRED, 'The service name to inspect')
            ->addOption('instantiate', 'i', InputOption::VALUE_NONE, 'Try to instantiate the service to get more details')
            ->setHelp('This command provides detailed information about a specific service.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $serviceName = $input->getArgument('service');
        $instantiate = $input->getOption('instantiate');

        try {
            if (!$this->serviceManager->has($serviceName)) {
                $io->error(sprintf('Service "%s" is not registered in the container.', $serviceName));
                return Command::FAILURE;
            }

            $this->displayServiceDetails($io, $serviceName, $instantiate);

        } catch (\Exception $e) {
            $io->error('Error inspecting service: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function displayServiceDetails(SymfonyStyle $io, string $serviceName, bool $instantiate): void
    {
        $io->title(sprintf('Service: %s', $serviceName));

        // Basic service information
        $details = [
            'Name' => $serviceName,
            'Registered' => $this->serviceManager->has($serviceName) ? 'Yes' : 'No',
        ];

        // Check if it's an alias
        if ($this->serviceManager->hasAlias($serviceName)) {
            $details['Type'] = 'Alias';
            $details['Target'] = $this->serviceManager->getAlias($serviceName);
        } else {
            $details['Type'] = 'Service';
        }

        // Check if it's shared
        $details['Shared'] = $this->serviceManager->isShared($serviceName) ? 'Yes' : 'No';

        // Check if it has a factory
        if ($this->serviceManager->hasFactory($serviceName)) {
            $factory = $this->serviceManager->getFactory($serviceName);
            $details['Factory'] = is_object($factory) ? get_class($factory) : (string) $factory;
        }

        // Check if it's an invokable
        if ($this->serviceManager->hasInvokableClass($serviceName)) {
            $details['Invokable Class'] = $this->serviceManager->getInvokableClass($serviceName);
        }

        $io->definitionList($details);

        // Try to instantiate if requested
        if ($instantiate) {
            $io->section('Service Instance');
            try {
                $service = $this->serviceManager->get($serviceName);
                $this->displayServiceInstance($io, $service);
            } catch (\Exception $e) {
                $io->error('Failed to instantiate service: ' . $e->getMessage());
            }
        }

        // Show related services (aliases pointing to this service)
        $this->displayRelatedServices($io, $serviceName);
    }

    private function displayServiceInstance(SymfonyStyle $io, mixed $service): void
    {
        if (is_object($service)) {
            $io->definitionList([
                'Class' => get_class($service),
                'Methods' => implode(', ', get_class_methods($service)),
            ]);

            // Try to get reflection information
            try {
                $reflection = new \ReflectionClass($service);
                $io->section('Reflection Information');
                
                $reflectionInfo = [
                    'Namespace' => $reflection->getNamespaceName(),
                    'Short Name' => $reflection->getShortName(),
                    'Is Abstract' => $reflection->isAbstract() ? 'Yes' : 'No',
                    'Is Interface' => $reflection->isInterface() ? 'Yes' : 'No',
                    'Is Trait' => $reflection->isTrait() ? 'Yes' : 'No',
                ];

                if ($reflection->getParentClass()) {
                    $reflectionInfo['Parent Class'] = $reflection->getParentClass()->getName();
                }

                $interfaces = $reflection->getInterfaceNames();
                if (!empty($interfaces)) {
                    $reflectionInfo['Interfaces'] = implode(', ', $interfaces);
                }

                $io->definitionList($reflectionInfo);
            } catch (\Exception $e) {
                $io->note('Could not get reflection information: ' . $e->getMessage());
            }
        } else {
            $io->definitionList([
                'Type' => gettype($service),
                'Value' => is_scalar($service) ? (string) $service : 'Non-scalar value',
            ]);
        }
    }

    private function displayRelatedServices(SymfonyStyle $io, string $serviceName): void
    {
        $aliases = [];
        
        try {
            $registeredServices = $this->serviceManager->getRegisteredServices();
            
            foreach ($registeredServices as $registeredService) {
                if ($this->serviceManager->hasAlias($registeredService)) {
                    $aliasTarget = $this->serviceManager->getAlias($registeredService);
                    if ($aliasTarget === $serviceName) {
                        $aliases[] = $registeredService;
                    }
                }
            }
            
            if (!empty($aliases)) {
                $io->section('Aliases');
                $io->listing($aliases);
            }
        } catch (\Exception $e) {
            // Ignore errors when getting related services
        }
    }
}
