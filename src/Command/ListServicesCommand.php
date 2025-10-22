<?php

declare(strict_types=1);

namespace Smoobu\LaminasServiceScanner\Command;

use Smoobu\LaminasServiceScanner\Interface\ServiceReaderInterface;
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
        private ServiceReaderInterface $serviceReader
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
            $services = $this->serviceReader->getServices($filter, $type);
            
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

    private function displaySimpleServices(SymfonyStyle $io, array $services): void
    {
        $io->title('Registered Services');
        
        $table = $io->createTable();
        $table->setHeaders(['Service Name', 'Type', 'Class/Value']);
        
        foreach ($services as $serviceInfo) {
            $table->addRow([
                $serviceInfo->name,
                $serviceInfo->type,
                $serviceInfo->class
            ]);
        }
        
        $table->render();
    }

    private function displayDetailedServices(SymfonyStyle $io, array $services): void
    {
        $io->title('Detailed Service Information');
        
        foreach ($services as $serviceInfo) {
            $io->section($serviceInfo->name);
            
            $details = [
                'Type' => $serviceInfo->type,
                'Class/Value' => $serviceInfo->class,
                'Shared' => $serviceInfo->isShared ? 'Yes' : 'No',
            ];
            
            if ($serviceInfo->isAliased) {
                $details['Aliases'] = implode(', ', $serviceInfo->aliases);
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
            $io->newLine();
        }
    }
}
