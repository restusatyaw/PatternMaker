<?php

namespace Restusatyaw\PattrenMaker\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class MakePattren extends Command
{
    protected $signature = 'make:pattren {name}';
    protected $description = 'Create Service, Repository, and DTO automatic';

    public function handle()
    {
        $name = $this->argument('name');
        $table = Str::plural(Str::snake($name)); // Convert to snake_case and pluralize
        
        // Verify if table exists
        if (!Schema::hasTable($table)) {
            $this->error("Table '{$table}' does not exist!");
            return;
        }

        // 1. Create Repositories
        $repositoryPath = app_path("Repositories/{$name}Repository.php");
        if (!File::exists(app_path('Repositories'))) {
            File::makeDirectory(app_path('Repositories'), 0755, true);
        }
        File::put($repositoryPath, $this->getRepositoryTemplate($name));
        $this->info("Repository created: {$repositoryPath}");

        // 2. Create Services
        $servicePath = app_path("Services/{$name}Service.php");
        if (!File::exists(app_path('Services'))) {
            File::makeDirectory(app_path('Services'), 0755, true);
        }
        File::put($servicePath, $this->getServiceTemplate($name));
        $this->info("Service created: {$servicePath}");

        // 3. Create DTO
        $dtoPath = app_path("DTOs/{$name}DTO.php");
        if (!File::exists(app_path('DTOs'))) {
            File::makeDirectory(app_path('DTOs'), 0755, true);
        }
        File::put($dtoPath, $this->getDTOTemplate($name, $table));
        $this->info("DTO created: {$dtoPath}");
        
        $this->info('✅ Services, Repositories And DTO successfully created!');
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

    private function getDTOTemplate($name, $table)
    {
        if (!Schema::hasTable($table)) {
            return "// Table {$table} does not exist";
        }

        $columns = Schema::getColumnListing($table);
        $properties = '';
        $toArray = '';
        
        foreach ($columns as $column) {
            $columnType = Schema::getColumnType($table, $column);
            
            // Map database types to PHP types
            $type = match(true) {
                in_array($columnType, ['integer', 'bigint', 'smallint']) => 'int',
                in_array($columnType, ['decimal', 'float', 'double']) => 'float',
                in_array($columnType, ['boolean']) => 'bool',
                in_array($columnType, ['datetime', 'timestamp']) => '?\\DateTime',
                default => 'string'
            };

            // All properties are nullable in DTO for flexibility
            $type = $type;

            $properties .= "    public {$type} \${$column};\n";
            $toArray .= "            '{$column}' => \$this->{$column},\n";
        }

        return <<<PHP
        <?php

        namespace App\DTOs;

        use JsonSerializable;
        use DateTime;

        class {$name}DTO implements JsonSerializable
        {
        {$properties}
            public function __construct(
                array \$data = []
            ) {
                foreach (\$data as \$key => \$value) {
                    if (property_exists(\$this, \$key)) {
                        if (\$value instanceof DateTime || (is_string(\$value) && strtotime(\$value))) {
                            \$this->\$key = \$value instanceof DateTime ? \$value : new DateTime(\$value);
                        } else {
                            \$this->\$key = \$value;
                        }
                    }
                }
            }

            public function toArray(): array
            {
                \$array = [
        {$toArray}
                ];

                // Convert DateTime objects to strings
                foreach (\$array as \$key => \$value) {
                    if (\$value instanceof DateTime) {
                        \$array[\$key] = \$value->format('Y-m-d H:i:s');
                    }
                }

                return \$array;
            }

            public function jsonSerialize(): array
            {
                return \$this->toArray();
            }
        }
        PHP;
    }
}