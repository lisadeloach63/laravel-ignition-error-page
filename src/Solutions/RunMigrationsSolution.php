<?php

namespace Facade\Ignition\Solutions;

use Illuminate\Support\Facades\Artisan;
use Facade\IgnitionContracts\RunnableSolution;

class RunMigrationsSolution implements RunnableSolution
{

    private $customTitle;

    public function __construct($customTitle = null)
    {
        $this->customTitle = $customTitle;
    }

    public function getSolutionTitle(): string
    {
        if (isset($this->customTitle)) {
            return $this->customTitle;
        }
        return 'A table was not found';
    }

    public function getSolutionDescription(): string
    {
        return 'You might have forgotten to run your migrations. You can run your migrations using `php artisan migrate`.';
    }

    public function getDocumentationLinks(): array
    {
        return [
            'Database: Running Migrations docs' => 'https://laravel.com/docs/5.8/migrations#running-migrations',
        ];
    }

    public function getRunParameters(): array
    {
        return [];
    }

    public function getSolutionActionDescription(): string
    {
        return 'Pressing the button below will try to run your migrations.';
    }

    public function getRunButtonText(): string
    {
        return 'Run migrations';
    }

    public function run(array $parameters = [])
    {
        Artisan::call('migrate');
    }
}
