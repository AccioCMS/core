<?php

namespace Accio\App\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class DBExport extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:export {--path}';

    /**
     * Export path
     *
     * @var string $exportPath
     */
    protected $exportPath = 'database/exports';

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
        if(!$exportPath){
            $exportPath = base_path($this->exportPath);
        }

        $filePath = $exportPath.'/'.date("Ymdhisi").'.sql';

        $command = 'mysqldump -h '.env('DB_HOST').' -u '.env('DB_USERNAME').' -p'.env('DB_PASSWORD').' '.env('DB_DATABASE').' > '.$filePath;

        $this->process->setCommandLine($command);
        $this->process->setTimeout(null);
        $this->process->run();

        if (!$this->process->isSuccessful()) {
            $this->error("Database not exported!");

            // Felete file if craeted by sql!
            if(File::exists($filePath)){
                File::delete($filePath);
            }

            return false;
        }else{
            $this->info("Database successfully exported to ".$filePath);
        }
    }

}
