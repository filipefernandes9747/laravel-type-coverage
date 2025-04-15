<?php

namespace Filipefernandes\LaravelTypeCoverage\Console;

use Illuminate\Console\Command;
use Filipefernandes\LaravelTypeCoverage\Scanner\FileScanner;
use Filipefernandes\LaravelTypeCoverage\Scanner\FunctionAnalyzer;

class CoverageCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laravel-type-coverage:run
                            {--path= : Comma-separated list of paths to scan}
                            {--fail-under= : Minimum coverage percentage to pass}
                            {--ignore= : Comma-separated list of paths to ignore}
                            {--export= : Export the report to a file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check type and doc coverage in your codebase';

    /**
     * Execute the console command.
     * @return int
     */
    public function handle(): int
    {
        $this->info('Running Laravel Type Coverage...');
        $this->info('Scanning for PHP files...');

        $paths = $this->option('path') ? explode(',', $this->option('path')) : config('type-coverage.paths', ['app']);
        $ignore = $this->option('ignore') ? explode(',', $this->option('ignore')) : config('type-coverage.ignore', []);
        $failUnder = $this->option('fail-under') ?: config('type-coverage.fail_under', 80);
        $exportable = $this->option('export') ?: config('type-coverage.export', true);

        $files = FileScanner::getPhpFiles($paths, $ignore);

        $total = 0;
        $covered = 0;

        $report = [];

        foreach ($files as $file) {
            $results = FunctionAnalyzer::analyze($file->getRealPath());

            foreach ($results as $result) {
                $total++;

                if ($result['has_doc'] && $result['has_type']) {
                    $covered++;
                } else {
                    $line = $result['line'] ?? '?';
                    $msgParts = [];

                    if (!$result['has_doc']) {
                        $msgParts[] = 'missing PHPDoc';
                    }
                    if (!$result['has_type']) {
                        $msgParts[] = 'missing type hints';
                    }

                    $message = "\u{26A0}\u{FE0F} {$result['function']} is " . implode(' and ', $msgParts) . ".";
                    $message .= "\n     \u{1F4A1} Consider adding type declarations or PHPDoc for better coverage.";

                    $relativePath = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $file->getRealPath());
                    $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', $relativePath); // Ensure consistency with slashes

                    $report[$relativePath][] = [
                        'line' => ":{$line}",
                        'function' => $result['function'],
                        'issues' => implode(' and ', $msgParts),
                    ];
                }
            }
        }

        ksort($report);

        if (!empty($report)) {
            foreach ($report as $file => $entries) {
                $this->line("\n------" . str_repeat('-', 86));
                $this->line("  File   {$file}");
                $this->line("------" . str_repeat('-', 86));
                $this->line("  Line   Message");
                $this->line("------" . str_repeat('-', 86));

                foreach ($entries as $entry) {
                    $this->line("  {$entry['line']}    ⚠️  Method \033[1m{$entry['function']}\033[0m is {$entry['issues']}.");
                    $this->line("         💡 Consider adding type declarations or PHPDoc for better coverage.");
                }
                $this->line("------" . str_repeat('-', 86) . "\n");
            }
        }

        $percentage = $total ? round(($covered / $total) * 100, 2) : 100;

        $this->info("✅ $covered / $total functions are documented and typed");
        $this->info("📊 Coverage: {$percentage}%");

        if ($exportable) {
            $path = $exportable ?? config('type-coverage.export_path', '');
            $filename = 'laravel-type-coverage' . now()->format('Y-m-d H:i:s') . '.json';

            // Ensure the directory exists
            if ($path && !file_exists($path)) {
                mkdir($path, 0777, true);
            }

            // Combine path and filename
            $fullFilePath = $path ? rtrim($path, '/') . '/' . $filename : $filename;

            // Export the results to the file
            file_put_contents($fullFilePath, json_encode($report, JSON_PRETTY_PRINT));
            $this->info("Coverage report exported to: {$filename}");
        }

        return $percentage < $failUnder ? Command::FAILURE : Command::SUCCESS;
    }
}
