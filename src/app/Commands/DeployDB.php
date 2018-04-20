<?php

namespace Accio\App\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class DeployDB extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deploy:db {--path} {--drop}';

    /**
     * Deployments path
     *
     * @var string $deploymentsPath
     */
    protected $deploymentsPath = 'database/deployments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deploy database to production';

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
     * @throws \Exception
     */
    public function handle()
    {
        $deploymentsPath = $this->option('path');
        if(!$deploymentsPath){
            $deploymentsPath = base_path($this->deploymentsPath);
        }

        // Drop tables
        if($this->option('drop')){
            $tables = DB::select('SHOW TABLES');
            if($tables) {
                $this->comment("\nDropping current tables");
                $this->dropAllTables();
                $this->info("Current tables dropped");
            }else{
                $this->comment("No tables to drop!");
            }
        }

        // Import sql files
        if(File::isDirectory($deploymentsPath)) {
            $sqlFiles = File::files($deploymentsPath);
            if ($sqlFiles) {
                $this->comment("\nImporting deployment SQL files");
                foreach ($sqlFiles as $file) {
                    if ($file->getExtension() == "sql") {
                        exec('mysql -h ' . env('DB_HOST') . ' -u ' . env('DB_USERNAME') . ' -p' . env('DB_PASSWORD') . ' ' . env('DB_DATABASE') . ' < ' . $file->getPathName() . '', $shellResponse, $status);

                        if ($status != 0) {
                            throw new \Exception("Database '" . $file->getPathName() . "' not deployed!");
                        }else {
                            // Delete file
                            File::delete($file->getPathName());
                        }
                    }
                }
                $this->info("Deployment SQL files imported!");
            }
        }
    }

    /**
     * Drop all of the database tables.
     *
     * @param  string  $database
     * @return void
     */
    protected function dropAllTables()
    {
        $this->laravel['db']->connection()
            ->getSchemaBuilder()
            ->dropAllTables();
    }

}
