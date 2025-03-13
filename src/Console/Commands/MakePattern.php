<?php

namespace Restusatyaw\PattrenMaker\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MakePattren extends Command
{
    protected $signature = 'make:Pattren {name} {--api : Generate API resources}';
    protected $description = 'Create Service, Repository, and Controller automatically';

    public function handle()
    {
        $this->info("MAKE SURE YOUR TABLE HAS BEEN MIGRATED TO DATABASE!");

        $name = $this->argument('name');
        $isApi = $this->option('api');
        $table = Str::plural(Str::snake($name)); // Convert to snake_case and pluralize
        
        // Verify if table exists
        if (!Schema::hasTable($table)) {
            $this->error("Table '{$table}' does not exist!");
            return;
        }

        // Create directory structure based on resource type
        $controllerPath = app_path("Http/Controllers");
        $servicePath = app_path("Services");
        
        if ($isApi) {
            // For API resources
            $controllerPath .= '/API';
            $servicePath .= '/API';
        } else {
            // For regular resources
            $controllerPath .= '/' . $name;
        }

        // Ensure directories exist
        if (!File::exists($controllerPath)) {
            File::makeDirectory($controllerPath, 0755, true);
        }
        
        if (!File::exists($servicePath)) {
            File::makeDirectory($servicePath, 0755, true);
        }
        
        if (!File::exists(app_path('Repositories'))) {
            File::makeDirectory(app_path('Repositories'), 0755, true);
        }

        // Create Repository
        $repositoryPath = app_path("Repositories/{$name}Repository.php");
        File::put($repositoryPath, $this->getRepositoryStub($name));
        $this->info("Repository created: {$repositoryPath}");

        // Create Service
        $serviceFileName = "{$name}Service.php";
        $serviceFilePath = "{$servicePath}/{$serviceFileName}";
        
        if ($isApi) {
            File::put($serviceFilePath, $this->getServiceApiStub($name));
        } else {
            File::put($serviceFilePath, $this->getServiceStub($name));
        }
        $this->info("Service created: {$serviceFilePath}");

        // Create Controller
        $controllerFileName = "{$name}Controller.php";
        $controllerFilePath = "{$controllerPath}/{$controllerFileName}";
        
        if ($isApi) {
            File::put($controllerFilePath, $this->getControllerApiStub($name));
        } else {
            File::put($controllerFilePath, $this->getControllerStub($name));
        }
        $this->info("Controller created: {$controllerFilePath}");
        
        $this->info('✅ Services, Repositories, and Controllers successfully created!');
    }

    /**
     * Get the repository stub content
     */
    protected function getRepositoryStub($name)
    {
        $stub = File::get(__DIR__ . '/../../Stubs/repository.stub');
        return $this->replacePlaceholders($stub, $name);
    }

    /**
     * Get the service stub content
     */
    protected function getServiceStub($name)
    {
        $stub = File::get(__DIR__ . '/../../Stubs/service.stub');
        return $this->replacePlaceholders($stub, $name);
    }

    /**
     * Get the API service stub content
     */
    protected function getServiceApiStub($name)
    {
        $stub = File::get(__DIR__ . '/../../Stubs/service-api.stub');
        return $this->replacePlaceholders($stub, $name);
    }

    /**
     * Get the controller stub content
     */
    protected function getControllerStub($name)
    {
        $stub = File::get(__DIR__ . '/../../Stubs/controller.stub');
        return $this->replacePlaceholders($stub, $name, false);
    }

    /**
     * Get the API controller stub content
     */
    protected function getControllerApiStub($name)
    {
        $stub = File::get(__DIR__ . '/../../Stubs/controller-api.stub');
        return $this->replacePlaceholders($stub, $name, true);
    }

    /**
     * Replace placeholders in stub files
     */
    protected function replacePlaceholders($stub, $name, $isApi = false)
    {
        $replacements = [
            '{{name}}' => $name,
            '{{namespace}}' => $this->getNamespace($name, $isApi),
            '{{class}}' => $name,
            '{{variable}}' => lcfirst($name),
            '{{model}}' => $name,
            '{{table}}' => Str::plural(Str::snake($name)),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $stub);
    }

    /**
     * Get the appropriate namespace based on resource type
     */
    protected function getNamespace($name, $isApi = false)
    {
        $baseNamespace = "App";
        
        if ($isApi) {
            return "{$baseNamespace}\\Http\\Controllers\\API";
        }
        
        return "{$baseNamespace}\\Http\\Controllers\\{$name}";
    }
}