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

        $this->info("MAKE SURE YOUR TABLE HAS BEEN MIGRATE TO DATABASE !");

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

            public function getFilteredData(\$filterData)
            {
                \$query = $name::query();

                // Dynamic search based on searchable columns
                if (isset(\$filterData['search']) && \$filterData['search'] != '') {
                    \$query->where(function(\$q) use (\$filterData) {
                        \$columns = Schema::getColumnListing((new $name)->getTable());
                        foreach(\$columns as \$column) {
                            \$q->orWhere(\$column, 'like', '%' . \$filterData['search'] . '%');
                        }
                    });
                }

                return \$query;
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

        use App\Repositories\\{$name}Repository;
        use App\DTOs\\{$name}DTO;
        use Illuminate\Support\Collection;
        use Yajra\DataTables\Facades\DataTables;

        class {$name}Service
        {
            protected \${$name}Repository;

            public function __construct({$name}Repository \${$name}Repository)
            {
                \$this->{$name}Repository = \${$name}Repository;
            }

            public function getAll(): Collection
            {
                \$data = \$this->{$name}Repository->getAll();
                return \$data->map(fn(\$item) => \$this->toDTO(\$item));
            }

            public function findById(\$id)
            {
                \$item = \$this->{$name}Repository->findById(\$id);
                return \$item ? \$this->toDTO(\$item) : null;
            }

            public function create(array \$data)
            {
                \$item = \$this->{$name}Repository->create(\$data);
                return \$this->toDTO(\$item);
            }

            public function update(\$id, array \$data)
            {
                \$item = \$this->{$name}Repository->update(\$id, \$data);
                return \$item ? \$this->toDTO(\$item) : null;
            }

            public function delete(\$id): bool
            {
                return \$this->{$name}Repository->delete(\$id);
            }

            public function getDatatable(array \$filterData)
            {
                \$query = \$this->{$name}Repository->getFilteredData(\$filterData);

                return DataTables::of(\$query)
                    ->addColumn('action', function (\$row) {
                        return view('components.action-buttons', [
                            'id' => \$row->id,
                            'editRoute' => route('{$name}.edit', \$row->id),
                            'deleteRoute' => route('{$name}.destroy', \$row->id)
                        ])->render();
                    })
                    ->rawColumns(['action'])
                    ->make(true);
            }

            private function toDTO(\$model): {$name}DTO
            {
                return new {$name}DTO(...\$model->toArray());
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
        }
        PHP;
    }
}
