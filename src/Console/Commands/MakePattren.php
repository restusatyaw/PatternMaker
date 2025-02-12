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

            public function getFilteredData(\$filterData)
            {
                \$query = $name::query();

                // Dynamic search based on searchable columns
                // if (isset(\$filterData['search']) && \$filterData['search'] != '') {
                    
                // }

                return \$query;
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
        use App\DTOs\{$name}DTO;
        use Illuminate\Support\Collection;
        use Yajra\DataTables\Facades\DataTables;
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
                    \$data = \$this->{$lower}Repository->getAll();
                    return \$data->map(fn(\$item) => \$this->toDTO(\$item));
                } catch (Exception \$e) {
                    Log::error("Error getting all {$lower}: " . \$e->getMessage());
                    return collect([]);
                }
            }
    
            public function findById(\$id)
            {
                try {
                    \$item = \$this->{$lower}Repository->findById(\$id);
                    return \$item ? \$this->toDTO(\$item) : null;
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
                    return \$this->toDTO(\$item);
                } catch (Exception \$e) {
                    DB::rollback();
                    Log::error("Error creating {$lower}: " . \$e->getMessage());
                    return null;
                }
            }
    
            public function update(\$id, array \$data)
            {
                DB::beginTransaction();
                try {
                    \$item = \$this->{$lower}Repository->update(\$id, \$data);
                    DB::commit();
                    return \$item ? \$this->toDTO(\$item) : null;
                } catch (Exception \$e) {
                    DB::rollback();
                    Log::error("Error updating {$lower} with ID {\$id}: " . \$e->getMessage());
                    return null;
                }
            }
    
            public function delete(\$id): bool
            {
                DB::beginTransaction();
                try {
                    \$deleted = \$this->{$lower}Repository->delete(\$id);
                    DB::commit();
                    return \$deleted;
                } catch (Exception \$e) {
                    DB::rollback();
                    Log::error("Error deleting {$lower} with ID {\$id}: " . \$e->getMessage());
                    return false;
                }
            }
    
            public function getDatatable(array \$filterData)
            {
                try {
                    \$query = \$this->{$lower}Repository->getFilteredData(\$filterData);
    
                    return DataTables::of(\$query)
                        ->addColumn('action', function (\$row) {
                            return view('backoffice.components.actionDatatable', [
                                'id' => \$row->id,
                                'url_edit' => route('backoffice.{$lower}.edit', \$row->id),
                                'url_delete' => route('backoffice.{$lower}.destroy', \$row->id),
                                'permission_edit' => '{$lower}.edit',
                                'permission_delete' => '{$lower}.delete',
                            ])->render();
                        })
                        ->rawColumns(['action'])
                        ->make(true);
                } catch (Exception \$e) {
                    Log::error("Error getting datatable for {$lower}: " . \$e->getMessage());
                    return response()->json(['error' => 'Failed to load data'], 500);
                }
            }
    
            private function toDTO(\$model): {$name}DTO
            {
                return new {$name}DTO(\$model->toArray());
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
    
            // Cek apakah kolom nullable
            $isNullable = $this->isColumnNullable($table, $column);
            $nullablePrefix = $isNullable ? '?' : '';
    
            // Map database types to PHP types
            $type = match (true) {
                in_array($columnType, ['integer', 'bigint', 'smallint']) => 'int',
                in_array($columnType, ['decimal', 'float', 'double']) => 'float',
                in_array($columnType, ['boolean']) => 'bool',
                in_array($columnType, ['datetime', 'timestamp']) => '\\DateTime',
                default => 'string'
            };
    
            $properties .= "    public {$nullablePrefix}{$type} \${$column};\n";
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
    
    /**
     * Cek apakah suatu kolom nullable di database.
     */
    private function isColumnNullable($table, $column)
    {
        $databaseName = config('database.connections.mysql.database');
    
        $result = DB::selectOne("
            SELECT IS_NULLABLE 
            FROM information_schema.columns 
            WHERE table_schema = ? AND table_name = ? AND column_name = ?",
            [$databaseName, $table, $column]
        );
    
        return $result->IS_NULLABLE === 'YES';
    }
    
    private function getControllerTemplate($name, $location)
    {
        $namespace = "App\\Http\\Controllers";
        if ($location) {
            $namespace .= "\\" . Str::studly($location);
        }
    
        $viewPath = strtolower(Str::kebab($name)); // Ubah menjadi format kebab-case untuk Blade view
        
        $lower = Str::lower($name);

        return <<<PHP
        <?php
    
        namespace {$namespace};
    
        use App\\Http\\Controllers\\Controller;
        use App\\Services\\{$name}Service;
        use Illuminate\\Http\\Request;
        use Illuminate\\Support\\Facades\\Log;
        use Exception;
    
        class {$name}Controller extends Controller
        {
            protected \${$name}Service;
    
            public function __construct({$name}Service \${$name}Service)
            {
                \$this->{$name}Service = \${$name}Service;
            }
    
            public function index()
            {
                try {
                    return view('backoffice.pages.{$viewPath}.index', [
                        'page_title' => '{$name}',
                        'urlCreate' => route('backoffice.{$lower}.create'),
                        'permission_create' => '{$lower}.create',
                    ]);
                } catch (Exception \$e) {
                    Log::error("Error loading index page for {$name}: " . \$e->getMessage());
                    return redirect()->back()->with('error', 'Failed to load page.');
                }
            }
    
            public function create()
            {
                try {
                    return view('backoffice.pages.{$viewPath}.create', [
                        'page_title' => 'Create {$name}',
                        'data' => null,
                        'isForm' => true,
                        'urlBack' => route('backoffice.{$lower}.index'),
                    ]);
                } catch (Exception \$e) {
                    Log::error("Error loading create page for {$name}: " . \$e->getMessage());
                    return redirect()->back()->with('error', 'Failed to load page.');
                }
            }
    
            public function show(\$id)
            {
                try {
                    return view('backoffice.pages.{$viewPath}.show', [
                        'page_title' => 'Detail {$name}',
                        'data' => \$this->{$name}Service->findById(\$id)
                    ]);
                } catch (Exception \$e) {
                    Log::error("Error loading show page for {$name} with ID {\$id}: " . \$e->getMessage());
                    return redirect()->back()->with('error', 'Failed to load page.');
                }
            }
    
            public function edit(\$id)
            {
                try {
                    return view('backoffice.pages.{$viewPath}.edit', [
                        'page_title' => 'Edit {$name}',
                        'data' => \$this->{$name}Service->findById(\$id),
                        'isForm' => true,
                        'urlBack' => route('backoffice.{$lower}.index'),
                    ]);
                } catch (Exception \$e) {
                    Log::error("Error loading edit page for {$name} with ID {\$id}: " . \$e->getMessage());
                    return redirect()->back()->with('error', 'Failed to load page.');
                }
            }
    
            public function store(Request \$request)
            {
                try {
                    \$this->{$name}Service->create(\$request->all());
                    return redirect()->route('{$viewPath}.index')->with('success', '{$name} created successfully.');
                } catch (Exception \$e) {
                    Log::error("Error storing {$name}: " . \$e->getMessage());
                    return redirect()->back()->with('error', 'Failed to create data.');
                }
            }
    
            public function update(Request \$request, \$id)
            {
                try {
                    \$this->{$name}Service->update(\$id, \$request->all());
                    return redirect()->route('{$viewPath}.index')->with('success', '{$name} updated successfully.');
                } catch (Exception \$e) {
                    Log::error("Error updating {$name} with ID {\$id}: " . \$e->getMessage());
                    return redirect()->back()->with('error', 'Failed to update data.');
                }
            }
    
            public function destroy(\$id)
            {
                try {
                    \$this->{$name}Service->delete(\$id);
                    return response()->json([
                        'status' => true,
                        'message' => '{$name} deleted successfully.'
                    ], 200);
                } catch (Exception \$e) {
                    Log::error("Error deleting {$name} with ID {\$id}: " . \$e->getMessage());
                    return response()->json([
                        'status' => false,
                        'message' => 'Failed to delete data.'
                    ], 500);
                }
            }
    
            public function getDatatable(Request \$request)
            {
                try {
                    return \$this->{$name}Service->getDatatable(\$request->all());
                } catch (Exception \$e) {
                    Log::error("Error getting datatable for {$name}: " . \$e->getMessage());
                    return response()->json(['error' => 'Failed to load data'], 500);
                }
            }
        }
        PHP;
    }
    
    
}
