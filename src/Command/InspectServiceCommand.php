<?php

declare(strict_types=1);

namespace Smoobu\LaminasServiceScanner\Command;

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
            if (!$this->serviceManager->has($serviceName)) {
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

    private function displayHiddenDependencies(SymfonyStyle $io, string $serviceName): void
    {
        $io->section('Hidden Dependencies Analysis');
        
        try {
            $service = $this->serviceManager->get($serviceName);
            
            if (!is_object($service)) {
                $io->note('Service is not an object, cannot analyze hidden dependencies.');
                return;
            }

            $reflection = new \ReflectionClass($service);
            $hiddenDeps = $this->analyzeHiddenDependencies($reflection);
            
            if (empty($hiddenDeps)) {
                $io->success('No hidden dependencies found using SR\\Di.');
                return;
            }

            $io->warning(sprintf('Found %d hidden dependency(ies):', count($hiddenDeps)));
            
            foreach ($hiddenDeps as $dep) {
                $io->definitionList([
                    'Service' => $dep['service'],
                    'File' => $dep['file'],
                    'Line' => $dep['line'],
                    'Context' => $dep['context'],
                ]);
                $io->newLine();
            }
            
        } catch (\Exception $e) {
            $io->error('Error analyzing hidden dependencies: ' . $e->getMessage());
        }
    }

    private function analyzeHiddenDependencies(\ReflectionClass $reflection): array
    {
        $hiddenDeps = [];
        
        // Check if class extends SR\Di\AbstractDi or uses SR\Di\DiTrait
        if (!$this->usesSRDi($reflection)) {
            return $hiddenDeps;
        }
        
        // Analyze current class and all parent classes
        $classesToAnalyze = [$reflection];
        
        // Add parent classes
        $parent = $reflection->getParentClass();
        while ($parent) {
            $classesToAnalyze[] = $parent;
            $parent = $parent->getParentClass();
        }

        foreach ($classesToAnalyze as $class) {
            $filePath = $class->getFileName();
            if (!$filePath || !file_exists($filePath)) {
                continue;
            }

            $deps = $this->scanFileForHiddenDeps($filePath, $class->getName());
            $hiddenDeps = array_merge($hiddenDeps, $deps);
        }

        return $hiddenDeps;
    }

    private function usesSRDi(\ReflectionClass $reflection): bool
    {
        // Check if extends SR\Di\AbstractDi
        $parent = $reflection;
        while ($parent) {
            if ($parent->getName() === 'SR\Di\AbstractDi') {
                return true;
            }
            $parent = $parent->getParentClass();
        }

        // Check if uses SR\Di\DiTrait
        $traits = $this->getAllTraits($reflection);
        foreach ($traits as $trait) {
            if ($trait->getName() === 'SR\Di\DiTrait') {
                return true;
            }
        }

        return false;
    }

    private function getAllTraits(\ReflectionClass $reflection): array
    {
        $traits = [];
        
        // Get traits from current class
        $traits = array_merge($traits, $reflection->getTraits());
        
        // Get traits from parent classes
        $parent = $reflection->getParentClass();
        while ($parent) {
            $traits = array_merge($traits, $parent->getTraits());
            $parent = $parent->getParentClass();
        }

        return $traits;
    }

    private function scanFileForHiddenDeps(string $filePath, string $className): array
    {
        $hiddenDeps = [];
        $content = file_get_contents($filePath);
        
        if (!$content) {
            return $hiddenDeps;
        }

        $lines = explode("\n", $content);
        
        // Pattern to match $this->getDi() calls
        $pattern = '/\$this\s*->\s*getDi\s*\(\s*[\'"]([^\'"]+)[\'"]?\s*\)/';
        
        foreach ($lines as $lineNumber => $line) {
            if (preg_match_all($pattern, $line, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[1] as $match) {
                    $serviceName = $match[0];
                    $offset = $match[1];
                    
                    // Get context around the match
                    $context = $this->getContextAroundMatch($line, $offset);
                    
                    $hiddenDeps[] = [
                        'service' => $serviceName,
                        'file' => $filePath,
                        'line' => $lineNumber + 1,
                        'context' => $context,
                    ];
                }
            }
        }

        return $hiddenDeps;
    }

    private function getContextAroundMatch(string $line, int $offset, int $contextLength = 50): string
    {
        $start = max(0, $offset - $contextLength);
        $end = min(strlen($line), $offset + $contextLength);
        
        $context = substr($line, $start, $end - $start);
        
        // Add ellipsis if we truncated
        if ($start > 0) {
            $context = '...' . $context;
        }
        if ($end < strlen($line)) {
            $context = $context . '...';
        }
        
        return trim($context);
    }
}
