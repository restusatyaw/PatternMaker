<?php
namespace Restusatyaw\PattrenMaker\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeFullPattren extends Command
{
    protected $signature = 'make:pattren-full {name}';
    protected $description = 'Membuat Model, Migration, Service, Repository, dan DTO sekaligus';

    public function handle()
    {
        $name = $this->argument('name');
        
        // 1. Buat Model dengan Migration & Seeder
        $this->call('make:model', [
            'name' => $name,
            '--migration' => true,
        ]);

        // 2. Buat Repository
        $repositoryPath = app_path("Repositories/{$name}Repository.php");
        if (!File::exists(app_path('Repositories'))) {
            File::makeDirectory(app_path('Repositories'), 0755, true);
        }
        File::put($repositoryPath, $this->getRepositoryTemplate($name));
        $this->info("Repository created: {$repositoryPath}");

        // 3. Buat Service
        $servicePath = app_path("Services/{$name}Service.php");
        if (!File::exists(app_path('Services'))) {
            File::makeDirectory(app_path('Services'), 0755, true);
        }
        File::put($servicePath, $this->getServiceTemplate($name));
        $this->info("Service created: {$servicePath}");

        // 4. Buat DTO
        $dtoPath = app_path("DTOs/{$name}DTO.php");
        if (!File::exists(app_path('DTOs'))) {
            File::makeDirectory(app_path('DTOs'), 0755, true);
        }
        File::put($dtoPath, $this->getDTOTemplate($name));
        $this->info("DTO created: {$dtoPath}");
        
        $this->info('✅ Semua file telah dibuat!');
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
        return <<<PHP
        <?php

        namespace App\Services;

        use App\Repositories\\$name.''.Repository;

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

    private function getDTOTemplate($name)
    {
        return <<<PHP
        <?php

        namespace App\DTOs;

        class {$name}DTO
        {
            public function __construct(
                public int \$id,
                public string \$name
            ) {}

            public static function fromModel(\$model): self
            {
                return new self(
                    id: \$model->id,
                    name: \$model->name
                );
            }
        }
        PHP;
    }
}
