<?php

namespace Restusatyaw\PattrenMaker\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MakePattren extends Command
{
    protected $signature = 'make:pattren {name}';
    protected $description = 'Create Service, Repository, and Controller automatically';

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

        // Create Controller
        $controllerFile = "{$controllerPath}/{$name}Controller.php";
        File::put($controllerFile, $this->getControllerTemplate($name, $controllerLocation));
        $this->info("Controller created: {$controllerFile}");
        
        $this->info('✅ Services, Repositories, and Controllers successfully created!');
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
        $name = Str::studly($name);
        $lower = Str::lower($name);
        return <<<PHP
        <?php
    
        namespace App\Services;
    
        use App\Repositories\{$name}Repository;
        use Illuminate\Support\Collection;
        use Illuminate\Support\Facades\Log;
        use Illuminate\Support\Facades\DB;
        use Exception;
    
        class {$name}Service
        {
            protected \${$lower}Repository;
    
            public function __construct({$name}Repository \${$lower}Repository)
            {
                \$this->{$lower}Repository = \${$lower}Repository;
            }
    
            public function getAll(): Collection
            {
                try {
                    return \$this->{$lower}Repository->getAll();
                } catch (Exception \$e) {
                    Log::error("Error getting all {$lower}: " . \$e->getMessage());
                    return collect([]);
                }
            }
    
            public function findById(\$id)
            {
                try {
                    return \$this->{$lower}Repository->findById(\$id);
                } catch (Exception \$e) {
                    Log::error("Error finding {$lower} with ID {\$id}: " . \$e->getMessage());
                    return null;
                }
            }
    
            public function create(array \$data)
            {
                DB::beginTransaction();
                try {
                    \$item = \$this->{$lower}Repository->create(\$data);
                    DB::commit();
                    return \$item;
                } catch (Exception \$e) {
                    DB::rollback();
                    Log::error("Error creating {$lower}: " . \$e->getMessage());
                    return null;
                }
            }
        }
        PHP;
    }
}
