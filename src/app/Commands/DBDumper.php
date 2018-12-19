<?php

namespace Accio\App\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use Spatie\DbDumper\Databases\Sqlite;
use Spatie\DbDumper\Databases\MongoDb;
use Spatie\DbDumper\Databases\MySql;
use Spatie\DbDumper\Databases\PostgreSql;

class DBDumper extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:dumper {--path}';

    /**
     * Export path
     *
     * @var string $exportPath
     */

    protected $exportPath = 'Dump database';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export db';

    /**
     * @var Process
     */
    protected $process;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->process = new Process($this);
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $exportPath = $this->option('path');

        if($exportPath) {
            if(!file_exists($exportPath)) {
                throw new \Exception('Path '.$exportPath. ' does not exists!');
            }
        }else{
            $exportPath = storage_path('app/dumper');

            // create dumper directory
            if(!file_exists($exportPath)) {
                File::makeDirectory($exportPath, 0755);
            }
        }

        $filePath = $exportPath.'/'.date("Ymdhisi").'.sql';
      
        $this->comment("Starting database dump...");

        switch (config('database.default')){
        case 'mysql':
            MySql::create()
                ->setDbName(config('database.connections.mysql.database'))
                ->setHost(config('database.connections.mysql.host'))
                ->setUserName(config('database.connections.mysql.username'))
                ->setPassword(config('database.connections.mysql.password'))
                ->dumpToFile($filePath);
            break;

        case 'pgsql':
            PostgreSql::create()
                ->setDbName(config('database.connections.pgsql.database'))
                ->setUserName(config('database.connections.pgsql.username'))
                ->setPassword(config('database.connections.pgsql.password'))
                ->dumpToFile($filePath);
            break;

        case 'sqlite':
            Sqlite::create()
                ->setDbName(config('database.connections.sqlite.database'))
                ->dumpToFile($filePath);
            break;

        case 'mongodb':
            MongoDb::create()
                ->setDbName(config('database.connections.mongodb.database'))
                ->setUserName(config('database.connections.mongodb.username'))
                ->setPassword(config('database.connections.mongodb.password'))
                ->dumpToFile($filePath);
            break;
        }

        $this->info("Database successfully dumped to ".$filePath);
    }

}
