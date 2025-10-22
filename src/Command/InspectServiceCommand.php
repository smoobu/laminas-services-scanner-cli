<?php

declare(strict_types=1);

namespace Smoobu\LaminasServiceScanner\Command;

use Smoobu\LaminasServiceScanner\Interface\ServiceReaderInterface;
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
        private ServiceReaderInterface $serviceReader
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('service', InputArgument::REQUIRED, 'The service name to inspect')
            ->addOption('instantiate', 'i', InputOption::VALUE_NONE, 'Try to instantiate the service to get more details')
            ->addOption('show-hidden-deps', 's', InputOption::VALUE_NONE, 'Scan for hidden dependencies using SR\\Di (AbstractDi/DiTrait)')
            ->setHelp('This command provides detailed information about a specific service.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $serviceName = $input->getArgument('service');
        $instantiate = $input->getOption('instantiate');
        $showHiddenDeps = $input->getOption('show-hidden-deps');

        try {
            if (!$this->serviceReader->hasService($serviceName)) {
                $io->error(sprintf('Service "%s" is not registered in the container.', $serviceName));
                return Command::FAILURE;
            }

            $this->displayServiceDetails($io, $serviceName, $instantiate, $showHiddenDeps);

        } catch (\Exception $e) {
            $io->error('Error inspecting service: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function displayServiceDetails(SymfonyStyle $io, string $serviceName, bool $instantiate, bool $showHiddenDeps): void
    {
        $io->title(sprintf('Service: %s', $serviceName));

        $serviceInfo = $this->serviceReader->getService($serviceName);
        if (!$serviceInfo) {
            $io->error('Service not found');
            return;
        }

        // Basic service information
        $details = [
            'Name' => $serviceInfo->name,
            'Type' => $serviceInfo->type,
            'Class/Value' => $serviceInfo->class,
            'Shared' => $serviceInfo->isShared ? 'Yes' : 'No',
        ];

        if ($serviceInfo->isAlias()) {
            $details['Target'] = $serviceInfo->class;
        }

        if ($serviceInfo->factory) {
            $details['Factory'] = $serviceInfo->factory;
        }

        if ($serviceInfo->invokableClass) {
            $details['Invokable Class'] = $serviceInfo->invokableClass;
        }

        if ($serviceInfo->hasError()) {
            $details['Error'] = $serviceInfo->error;
        }

        $io->definitionList($details);

        // Try to instantiate if requested
        if ($instantiate) {
            $io->section('Service Instance');
            try {
                $service = $this->serviceReader->getServiceInstance($serviceName);
                $this->displayServiceInstance($io, $service);
            } catch (\Exception $e) {
                $io->error('Failed to instantiate service: ' . $e->getMessage());
            }
        }

        // Show related services (aliases pointing to this service)
        $this->displayRelatedServices($io, $serviceName);

        // Show hidden dependencies if requested
        if ($showHiddenDeps) {
            $this->displayHiddenDependencies($io, $serviceName);
        }
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
            $allServices = $this->serviceReader->getAllServices();
            
            foreach ($allServices as $serviceInfo) {
                if ($serviceInfo->isAlias() && $serviceInfo->class === $serviceName) {
                    $aliases[] = $serviceInfo->name;
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

    private function displayHiddenDependencies(SymfonyStyle $io, string $serviceName): void
    {
        $io->section('Hidden Dependencies Analysis');
        
        try {
            $hiddenDeps = $this->serviceReader->getHiddenDependencies($serviceName);
            
            if (empty($hiddenDeps)) {
                $io->success('No hidden dependencies found using SR\\Di.');
                return;
            }

            $io->warning(sprintf('Found %d hidden dependency(ies):', count($hiddenDeps)));
            
            foreach ($hiddenDeps as $dep) {
                $io->definitionList([
                    'Service' => $dep->service,
                    'File' => $dep->file,
                    'Line' => $dep->line,
                    'Context' => $dep->context,
                ]);
                $io->newLine();
            }
            
        } catch (\Exception $e) {
            $io->error('Error analyzing hidden dependencies: ' . $e->getMessage());
        }
    }

}
