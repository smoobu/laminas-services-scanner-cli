<?php

declare(strict_types=1);

namespace Smoobu\LaminasServiceScanner\Service;

use Smoobu\LaminasServiceScanner\DTO\HiddenDependency;

class ScanFileForHiddenDeps
{
    public function scan(string $filePath, string $className): array
    {
        $hiddenDeps = [];
        $content = file_get_contents($filePath);
        
        if (!$content) {
            return $hiddenDeps;
        }

        $lines = explode("\n", $content);
        
        // Pattern to match $this->getDi() calls
        $getDiPattern = '/\$this\s*->\s*getDi\s*\(\s*[\'"]([^\'"]+)[\'"]?\s*\)/';
        
        // Pattern to match Registry::get() calls
        $registryPattern = '/Registry\s*::\s*get\s*\(\s*[\'"]([^\'"]+)[\'"]?\s*\)/';
        
        foreach ($lines as $lineNumber => $line) {
            // Check for $this->getDi() calls
            if (preg_match_all($getDiPattern, $line, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[1] as $match) {
                    $serviceName = $match[0];
                    $offset = $match[1];
                    
                    // Get context around the match
                    $context = $this->getContextAroundMatch($line, $offset);
                    
                    $hiddenDeps[] = new HiddenDependency(
                        service: $serviceName,
                        file: $filePath,
                        line: $lineNumber + 1,
                        context: $context
                    );
                }
            }
            
            // Check for Registry::get() calls
            if (preg_match_all($registryPattern, $line, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[1] as $match) {
                    $serviceName = $match[0];
                    $offset = $match[1];
                    
                    // Get context around the match
                    $context = $this->getContextAroundMatch($line, $offset);
                    
                    $hiddenDeps[] = new HiddenDependency(
                        service: $serviceName,
                        file: $filePath,
                        line: $lineNumber + 1,
                        context: $context
                    );
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
