<?php

namespace Restusatyaw\PattrenMaker\Providers;

use Illuminate\Support\ServiceProvider;
use Restusatyaw\PattrenMaker\Console\Commands\MakeAutomaticSeeder;
use Restusatyaw\PattrenMaker\Console\Commands\MakePattren;
use Restusatyaw\PattrenMaker\Console\Commands\MakeFullPattren;

class PattrenMakerServiceProvider extends ServiceProvider
{
    /**
     * Daftarkan layanan di dalam container.
     *
     * @return void
     */
    public function register()
    {
        // Daftarkan command jika kamu ingin membuat perintah Artisan
        $this->commands([
            MakeAutomaticSeeder::class,
            MakePattren::class,
            MakeFullPattren::class,
        ]);
    }

    /**
     * Melakukan bootstrap aplikasi.
     *
     * @return void
     */
    public function boot()
    {
        // Jika kamu memiliki file konfigurasi atau resource lain yang perlu dipublikasikan
        // $this->publishes([
        //     __DIR__.'/path/to/config/config.php' => config_path('pattrenmaker.php'),
        // ]);
    }
}
