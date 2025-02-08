<?php 

namespace Restusatyaw\PattrenMaker\Providers;

use Illuminate\Support\ServiceProvider;
use Restusatyaw\PattrenMaker\Commands\MakeSeederAuto;
use Restusatyaw\PattrenMaker\Commands\MakePattren;
use Restusatyaw\PattrenMaker\Commands\MakePattrenFull;

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
            MakeSeederAuto::class,
            MakePattren::class,
            MakePattrenFull::class,
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
