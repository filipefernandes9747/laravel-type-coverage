# Laravel Type Coverage

**Laravel Type Coverage** is a package that helps you ensure your codebase has proper type hints and PHPDoc coverage. It analyzes your PHP files for missing type hints and doc comments, generating a report with insights on which functions need improvement.

## Features

- Analyze your codebase for functions without PHPDoc and missing type hints.
- Display detailed coverage information with suggestions for improvement.
- Export the results to a JSON file.
- Supports configurable paths and exclusions for scanning.
- Command-line interface for easy integration into your workflow.

## Installation

### 1. Install via Composer

To install the package, run the following command in your Laravel project:

```bash
composer require filipefernandes/laravel-type-coverage --dev
```

### 2. Publish Configuration (Optional)

If you want to customize the default configuration for the paths to scan, exclusions, or export options, you can publish the configuration file:

```bash
php artisan vendor:publish --provider="Filipefernandes\LaravelTypeCoverage\LaravelTypeCoverageServiceProvider" --tag="config"
```

This will create a `config/type-coverage.php` file where you can adjust the settings.

## Usage

### Run the Command

You can run the coverage analysis command with:

```bash
php artisan laravel-type-coverage:run
```

This command will scan your project for PHP functions and check if they have both type hints and PHPDoc comments. It will display a report in the terminal, showing the missing type hints and PHPDoc, along with suggested improvements.

#### Command Options:

- `--path`: Comma-separated list of paths to scan (e.g., `app,src`). If not provided, it will default to `app`.
- `--fail-under`: Set the minimum coverage percentage required to pass (default: `80`).
- `--ignore`: Comma-separated list of paths to ignore.
- `--export`: Export the report to a file (default is `true`).

#### Example Usage:

```bash
php artisan laravel-type-coverage:run --path=app,src --fail-under=90 --export
```

### Output Example:

When you run the command, you’ll see output like this:

```
Running Laravel Type Coverage...
Scanning for PHP files...

--------------------------------------------------------------------------------------------
  File   app/Models/ZipCode.php
--------------------------------------------------------------------------------------------
  Line   Message
--------------------------------------------------------------------------------------------
  :83    ⚠️  Method getFullZipCodeAttribute is missing type hints.
         💡 Consider adding type declarations or PHPDoc for better coverage.
  :92    ⚠️  Method getFullZipCodeInfoAttribute is missing PHPDoc and missing type hints.
         💡 Consider adding type declarations or PHPDoc for better coverage.
--------------------------------------------------------------------------------------------

Coverage: 85%
```

### Exporting Results:

By default, the tool will export the results to `laravel-type-coverage.json` in your project’s root directory. You can customize the export path in the configuration file.

### Example Export:

If you want to specify a custom export path:

```bash
php artisan laravel-type-coverage:run --export=/path/to/export.json
```

## Configuration

The configuration file `config/type-coverage.php` contains the following options:

- `paths`: Array of paths to scan (default: `['app']`).
- `ignore`: Array of paths to ignore (default: `[]`).
- `fail_under`: Minimum coverage percentage required to pass (default: `80`).
- `export`: Whether to export the results to a JSON file (default: `true`).
- `export_path`: The directory or path to export the coverage report (default: current directory).

Example:

```php
return [
    'paths' => ['app', 'src'],
    'ignore' => ['vendor', 'storage'],
    'fail_under' => 85,
    'export' => true,
    'export_path' => 'coverage_reports/',
];
```

## Contributing

Contributions are welcome! If you'd like to contribute to the package, feel free to open a pull request with your changes.

## License

This package is open-source software licensed under the MIT License.
