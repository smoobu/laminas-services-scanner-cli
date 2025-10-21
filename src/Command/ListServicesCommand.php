<?php

declare(strict_types=1);

namespace Smoobu\LaminasServiceScanner\Command;

use Laminas\ServiceManager\ServiceManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'services:list',
    description: 'List all registered services in the Laminas container'
)]
class ListServicesCommand extends Command
{
    public function __construct(
        private ServiceManager $serviceManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('filter', 'f', InputOption::VALUE_OPTIONAL, 'Filter services by name pattern')
            ->addOption('type', 't', InputOption::VALUE_OPTIONAL, 'Filter services by type (class, interface, alias)')
            ->addOption('detailed', 'd', InputOption::VALUE_NONE, 'Show detailed information about each service')
            ->setHelp('This command lists all registered services in the Laminas ServiceManager container.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $filter = $input->getOption('filter');
        $type = $input->getOption('type');
        $detailed = $input->getOption('detailed');

        try {
            $services = $this->getServices($filter, $type);
            
            if (empty($services)) {
                $io->warning('No services found matching the criteria.');
                return Command::SUCCESS;
            }

            if ($detailed) {
                $this->displayDetailedServices($io, $services);
            } else {
                $this->displaySimpleServices($io, $services);
            }

            $io->success(sprintf('Found %d service(s)', count($services)));
            
        } catch (\Exception $e) {
            $io->error('Error retrieving services: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function getServices(?string $filter, ?string $type): array
    {
        $services = [];
        
        // Get all registered service names
        $registeredServices = $this->serviceManager->getRegisteredServices();
        
        foreach ($registeredServices as $serviceName) {
            // Apply filter if provided
            if ($filter && !str_contains(strtolower($serviceName), strtolower($filter))) {
                continue;
            }

            try {
                $serviceInfo = $this->getServiceInfo($serviceName);
                
                // Apply type filter if provided
                if ($type && $serviceInfo['type'] !== $type) {
                    continue;
                }
                
                $services[$serviceName] = $serviceInfo;
            } catch (\Exception $e) {
                // Skip services that can't be inspected
                $services[$serviceName] = [
                    'type' => 'unknown',
                    'class' => 'Error: ' . $e->getMessage(),
                    'is_shared' => false,
                    'is_aliased' => false,
                    'aliases' => []
                ];
            }
        }

        ksort($services);
        return $services;
    }

    private function getServiceInfo(string $serviceName): array
    {
        $info = [
            'type' => 'service',
            'class' => 'unknown',
            'is_shared' => false,
            'is_aliased' => false,
            'aliases' => []
        ];

        try {
            // Check if it's an alias
            if ($this->serviceManager->hasAlias($serviceName)) {
                $info['type'] = 'alias';
                $info['is_aliased'] = true;
                $info['class'] = $this->serviceManager->getAlias($serviceName);
                return $info;
            }

            // Check if it's a factory
            if ($this->serviceManager->hasFactory($serviceName)) {
                $info['type'] = 'factory';
                $factory = $this->serviceManager->getFactory($serviceName);
                $info['class'] = is_object($factory) ? get_class($factory) : (string) $factory;
            }

            // Check if it's an invokable
            if ($this->serviceManager->hasInvokableClass($serviceName)) {
                $info['type'] = 'invokable';
                $info['class'] = $this->serviceManager->getInvokableClass($serviceName);
            }

            // Check if it's a service
            if ($this->serviceManager->has($serviceName)) {
                $service = $this->serviceManager->get($serviceName);
                if (is_object($service)) {
                    $info['class'] = get_class($service);
                } else {
                    $info['class'] = gettype($service);
                }
            }

            // Check if it's shared
            $info['is_shared'] = $this->serviceManager->isShared($serviceName);

        } catch (\Exception $e) {
            $info['class'] = 'Error: ' . $e->getMessage();
        }

        return $info;
    }

    private function displaySimpleServices(SymfonyStyle $io, array $services): void
    {
        $io->title('Registered Services');
        
        $table = $io->createTable();
        $table->setHeaders(['Service Name', 'Type', 'Class/Value']);
        
        foreach ($services as $name => $info) {
            $table->addRow([
                $name,
                $info['type'],
                $info['class']
            ]);
        }
        
        $table->render();
    }

    private function displayDetailedServices(SymfonyStyle $io, array $services): void
    {
        $io->title('Detailed Service Information');
        
        foreach ($services as $name => $info) {
            $io->section($name);
            
            $details = [
                'Type' => $info['type'],
                'Class/Value' => $info['class'],
                'Shared' => $info['is_shared'] ? 'Yes' : 'No',
            ];
            
            if ($info['is_aliased']) {
                $details['Aliases'] = implode(', ', $info['aliases']);
            }
            
            $io->definitionList($details);
            $io->newLine();
        }
    }
}
