<?php

namespace Restusatyaw\PattrenMaker\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MakePattren extends Command
{
    protected $signature = 'make:pattren {name}';
    protected $description = 'Create Service, Repository, DTO, and Controller automatically';

    public function handle()
    {
        $this->info("MAKE SURE YOUR TABLE HAS BEEN MIGRATED TO DATABASE!");

        $name = $this->argument('name');
        $table = Str::plural(Str::snake($name)); // Convert to snake_case and pluralize
        
        // Verify if table exists
        if (!Schema::hasTable($table)) {
            $this->error("Table '{$table}' does not exist!");
            return;
        }

        // Ask for Controller location
        $controllerLocation = $this->ask("Where do you need the controller location? (default: app/Http/Controllers)");
        $controllerPath = app_path("Http/Controllers");
        
        if ($controllerLocation) {
            $controllerPath .= '/' . Str::studly($controllerLocation);
        }

        // Ensure directory exists
        if (!File::exists($controllerPath)) {
            File::makeDirectory($controllerPath, 0755, true);
        }

        // Create Repositories
        $repositoryPath = app_path("Repositories/{$name}Repository.php");
        if (!File::exists(app_path('Repositories'))) {
            File::makeDirectory(app_path('Repositories'), 0755, true);
        }
        File::put($repositoryPath, $this->getRepositoryTemplate($name));
        $this->info("Repository created: {$repositoryPath}");

        // Create Services
        $servicePath = app_path("Services/{$name}Service.php");
        if (!File::exists(app_path('Services'))) {
            File::makeDirectory(app_path('Services'), 0755, true);
        }
        File::put($servicePath, $this->getServiceTemplate($name));
        $this->info("Service created: {$servicePath}");

        // Create DTO
        $dtoPath = app_path("DTOs/{$name}DTO.php");
        if (!File::exists(app_path('DTOs'))) {
            File::makeDirectory(app_path('DTOs'), 0755, true);
        }
        File::put($dtoPath, $this->getDTOTemplate($name, $table));
        $this->info("DTO created: {$dtoPath}");

        // Create Controller
        $controllerFile = "{$controllerPath}/{$name}Controller.php";
        File::put($controllerFile, $this->getControllerTemplate($name, $controllerLocation));
        $this->info("Controller created: {$controllerFile}");
        
        $this->info('✅ Services, Repositories, DTOs, and Controllers successfully created!');
    }

    private function getRepositoryTemplate($name)
    {
        return <<<PHP
        <?php

        namespace App\Repositories;

        use App\Models\\$name;

        class {$name}Repository
        {
            public function getAll()
            {
                return $name::all();
            }

            public function findById(\$id)
            {
                return $name::find(\$id);
            }

            public function create(array \$data)
            {
                return $name::create(\$data);
            }

            public function update(\$id, array \$data)
            {
                \$model = $name::find(\$id);
                if (\$model) {
                    \$model->update(\$data);
                    return \$model;
                }
                return null;
            }

            public function delete(\$id)
            {
                return $name::destroy(\$id);
            }
        }
        PHP;
    }

    private function getServiceTemplate($name)
    {
        return <<<PHP
        <?php

        namespace App\Services;

        use App\Repositories\\{$name}Repository;
        use App\DTOs\\{$name}DTO;

        class {$name}Service
        {
            protected \${$name}Repository;

            public function __construct({$name}Repository \${$name}Repository)
            {
                \$this->{$name}Repository = \${$name}Repository;
            }

            public function getAll()
            {
                return \$this->{$name}Repository->getAll();
            }

            public function findById(\$id)
            {
                return \$this->{$name}Repository->findById(\$id);
            }

            public function create(array \$data)
            {
                return \$this->{$name}Repository->create(\$data);
            }

            public function update(\$id, array \$data)
            {
                return \$this->{$name}Repository->update(\$id, \$data);
            }

            public function delete(\$id)
            {
                return \$this->{$name}Repository->delete(\$id);
            }
        }
        PHP;
    }

    private function getDTOTemplate($name, $table)
    {
        $columns = Schema::getColumnListing($table);
        $properties = '';
        
        foreach ($columns as $column) {
            $properties .= "    public \${$column};\n";
        }

        return <<<PHP
        <?php

        namespace App\DTOs;

        class {$name}DTO
        {
            {$properties}

            public function __construct(array \$data)
            {
                foreach (\$data as \$key => \$value) {
                    \$this->\$key = \$value;
                }
            }
        }
        PHP;
    }

    private function getControllerTemplate($name, $location)
    {
        $namespace = "App\Http\Controllers";
        if ($location) {
            $namespace .= "\\" . Str::studly($location);
        }
    
        $viewPath = strtolower(Str::kebab($name)); // Ubah menjadi format kebab-case untuk Blade view
    
        return <<<PHP
        <?php
    
        namespace {$namespace};
    
        use App\Http\Controllers\Controller;
        use App\Services\\{$name}Service;
        use Illuminate\Http\Request;
    
        class {$name}Controller extends Controller
        {
            protected \${$name}Service;
    
            public function __construct({$name}Service \${$name}Service)
            {
                \$this->{$name}Service = \${$name}Service;
            }
    
            public function index()
            {
                return view('backoffice.pages.{$viewPath}.index', [
                    'page_title' => '{$name} Management'
                ]);
            }
    
            public function create()
            {
                return view('backoffice.pages.{$viewPath}.create', [
                    'page_title' => 'Create {$name}'
                ]);
            }
    
            public function show(\$id)
            {
                return view('backoffice.pages.{$viewPath}.show', [
                    'page_title' => 'Detail {$name}',
                    'data' => \$this->{$name}Service->findById(\$id)
                ]);
            }
    
            public function edit(\$id)
            {
                return view('backoffice.pages.{$viewPath}.edit', [
                    'page_title' => 'Edit {$name}',
                    'data' => \$this->{$name}Service->findById(\$id)
                ]);
            }
    
            public function store(Request \$request)
            {
                \$this->{$name}Service->create(\$request->all());
                return redirect()->route('{$viewPath}.index')->with('success', '{$name} created successfully.');
            }
    
            public function update(Request \$request, \$id)
            {
                \$this->{$name}Service->update(\$id, \$request->all());
                return redirect()->route('{$viewPath}.index')->with('success', '{$name} updated successfully.');
            }
    
            public function destroy(\$id)
            {
                \$this->{$name}Service->delete(\$id);
                return redirect()->route('{$viewPath}.index')->with('success', '{$name} deleted successfully.');
            }
    
            public function getDatatable(Request \$request)
            {
                return \$this->{$name}Service->getDatatable(\$request->all());
            }
        }
        PHP;
    }
    
}
