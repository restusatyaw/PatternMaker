<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakePattren extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:pattren {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Service, Repository, dan DTO automatic';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->argument('name');
        
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
        File::put($dtoPath, $this->getDTOTemplate($name));
        $this->info("DTO created: {$dtoPath}");
        
        $this->info('✅ Services, Repositories And DTO already success created!');
    }

    private function getRepositoryTemplate($name)
    {
        return <<<PHP
        <?php

        namespace App\Repositories;

        class {$name}Repository
        {
            public function getAll()
            {
                // Implementasi get all data
            }

            public function findById(\$id)
            {
                // Implementasi find by id
            }

            public function create(array \$data)
            {
                // Implementasi create data
            }

            public function update(\$id, array \$data)
            {
                // Implementasi update data
            }

            public function delete(\$id)
            {
                // Implementasi delete data
            }
        }
        PHP;
    }

    private function getServiceTemplate($name)
    {
        return <<<PHP
        <?php

        namespace App\Services;

        use App\Repositories\{$name}Repository;

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
        }
        PHP;
    }
}
