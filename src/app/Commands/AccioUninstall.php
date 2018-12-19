<?php

namespace Accio\App\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Accio\App\Traits\OutputStyles;
use Accio\PackageServiceProvider;

class AccioUninstall extends Command
{

    use OutputStyles;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'accio:uninstall';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Uninstall CMS';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        $this->clearCaches();

        // Make sure app is not installed
        // Ask to confirm
        $response = $this->ask('If you app is currently installed all your data will be deleted. Type DELETE if you really want to proceed!');
        if($response != 'DELETE') {
            $this->comment('Reinstall process abandoned!');
            return;
        }

        // Delete Uploads
        $this->comment("Cleaning directories");
        $this->cleanDirectories();

        if(file_exists(app()->environmentFilePath())) {
            // drop tables only if app is installed
            if(env('APP_KEY') !== 'SomeRandomString') {
                $this->comment("Dropping database tables");
                $this->dropAllTables();
            }

            // Delete env
            $this->comment("Deleting .env file");
            File::delete(app()->environmentFilePath());
        }

        $this->clearCaches();

        $this->block(' Successfully uninstalled', 'fg=white;bg=green;options=bold');
        $this->line('');
    }

    /**
     * Clean directories
     *
     * @return void
     */
    private function cleanDirectories()
    {
        // Clean uploads
        $success = File::cleanDirectory(uploadsPath());
        if(!$success) {
            $this->info("Directory ".uploadsPath()." could not be deleted. Please delete it manually!");
        }

        return;
    }

    /**
     * Drop all of the database tables.
     *
     * @param  string $database
     * @return void
     */
    protected function dropAllTables()
    {
        $this->laravel['db']->connection()
            ->getSchemaBuilder()
            ->dropAllTables();
    }


    /**
     * Clears all Laravel caches.
     */
    protected function clearCaches()
    {
        $this->callSilent('clear-compiled');
        $this->callSilent('cache:clear');
        $this->callSilent('route:clear');
        $this->callSilent('config:clear');
        $this->callSilent('view:clear');
        Cache::flush();
    }
}
