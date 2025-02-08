<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class GenerateSeeder extends Command
{
    protected $signature = 'make:seeder-auto {table}';
    protected $description = 'Membuat seeder secara otomatis berdasarkan migration dengan UUID & relasi';

    public function handle()
    {
        $table = $this->argument('table');
        $className = Str::studly($table) . 'Seeder';
        $seederPath = database_path("seeders/{$className}.php");

        // Ambil struktur kolom dari tabel
        $columns = DB::select("SHOW COLUMNS FROM {$table}");

        if (empty($columns)) {
            $this->error("Tabel {$table} tidak ditemukan di database.");
            return;
        }

        // Generate data dengan UUID & relasi
        $fakerData = $this->generateFakerData($columns, $table);

        // Template Seeder
        $seederTemplate = $this->getSeederTemplate($className, $table, $fakerData);

        // Buat file seeder
        File::put($seederPath, $seederTemplate);

        $this->info("Seeder berhasil dibuat: {$seederPath}");
    }

    private function generateFakerData($columns, $table)
    {
        $fakerLines = [];
        foreach ($columns as $column) {
            $name = $column->Field;
            $type = $column->Type;

            // UUID sebagai primary key
            if ($name === 'id') {
                $fakerLines[] = "'id' => Str::uuid(),";
                continue;
            }

            // Cek apakah kolom foreign key
            if (Str::endsWith($name, '_id')) {
                $relatedTable = Str::plural(str_replace('_id', '', $name));
                $fakerLines[] = "'{$name}' => DB::table('{$relatedTable}')->inRandomOrder()->value('id') ?? Str::uuid(),";
                continue;
            }

            // Generate data berdasarkan tipe kolom
            $fakerLines[] = "'{$name}' => " . $this->getFakerType($type) . ",";
        }
        return implode("\n                ", $fakerLines);
    }

    private function getFakerType($type)
    {
        if (Str::contains($type, 'int')) return 'fake()->randomNumber()';
        if (Str::contains($type, 'varchar') || Str::contains($type, 'text')) return 'fake()->sentence()';
        if (Str::contains($type, 'date')) return 'fake()->date()';
        if (Str::contains($type, 'timestamp')) return 'now()';
        if (Str::contains($type, 'boolean')) return 'fake()->boolean()';
        if (Str::contains($type, 'decimal') || Str::contains($type, 'float')) return 'fake()->randomFloat(2, 1, 100)';

        return 'fake()->word()'; // Default fallback
    }

    private function getSeederTemplate($className, $table, $fakerData)
    {
        return <<<PHP
        <?php

        namespace Database\\Seeders;

        use Illuminate\\Database\\Seeder;
        use Illuminate\\Support\\Facades\\DB;
        use Illuminate\\Support\\Facades\\Schema;
        use Illuminate\\Support\\Str;

        class {$className} extends Seeder
        {
            public function run()
            {
                if (!Schema::hasTable('{$table}')) {
                    echo "Table '{$table}' not found! Skipping..."; 
                    return;
                }
                
                DB::table('{$table}')->insert([
                    [
                        {$fakerData}
                    ]
                ]);
            }
        }
        PHP;
    }
}
