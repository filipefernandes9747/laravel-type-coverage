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
                            {--ignore= : Comma-separated list of paths to ignore}';

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

                    $report[$file->getRelativePathname()][] = [
                        'line' => ":{$line}",
                        'message' => "{$result['function']} is " . implode(' and ', $msgParts) .
                            "\n 💡 Add type declarations or PHPDoc for better coverage.",
                    ];
                }
            }
        }

        if (!empty($report)) {
            foreach ($report as $file => $entries) {
                $this->line("\n ------ " . str_pad("{$file}", 86, '-') . "\n");
                $this->line("  File   {$file}");
                $this->line(" ------ " . str_repeat('-', 86));

                foreach ($entries as $entry) {
                    $this->line("  {$entry['line']}   {$entry['message']}");
                }

                $this->line(" ------ " . str_repeat('-', 86) . "\n");
            }
        }

        $percentage = $total ? round(($covered / $total) * 100, 2) : 100;

        $this->info("✅ $covered / $total functions are documented and typed");
        $this->info("📊 Coverage: {$percentage}%");



        if ($percentage < $failUnder) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
