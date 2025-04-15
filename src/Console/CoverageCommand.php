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

        foreach ($files as $file) {
            $results = FunctionAnalyzer::analyze($file->getRealPath());

            foreach ($results as $result) {
                $total++;
                if ($result['has_doc'] && $result['has_type']) {
                    $covered++;
                } else {
                    $this->line("✘ {$result['function']} in {$file->getRelativePathname()}");
                }
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
