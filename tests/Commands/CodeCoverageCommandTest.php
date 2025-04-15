<?php

namespace Filipefernandes\LaravelTypeCoverage\Tests\Commands;

use Filipefernandes\LaravelTypeCoverage\Tests\TestCase;

uses(TestCase::class);

it('can run the code coverage command', function (): void {

    $this->artisan('laravel-type-coverage:run --path=src')
        ->expectsOutput('Coverage:')
        ->assertExitCode(0);
});
