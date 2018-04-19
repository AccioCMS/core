<?php

namespace Accio\App\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class DeployClean extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deploy:clean';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean caches on deploy';

    /**
     *
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    function dirIsEmpty($dir) {
        $handle = opendir($dir);
        $ignoreList = [
            ".",
            "..",
            '.gitignore',
            '.keep'
        ];
        while (false !== ($entry = readdir($handle))) {
            if (!in_array($entry, $ignoreList)) {
                return FALSE;
            }
        }
        return TRUE;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(){
        if(config('deploy.clean_directories')){
            $this->comment("\nCleaning directories");
            foreach(config('deploy.clean_directories') as $directory){
                if(file_exists($directory) && !$this->dirIsEmpty($directory)) {
                    $endsWith = substr($directory, 1);

                    // add asterix
                    if ($endsWith == '/') {
                        $directory = $directory."*";
                    }
                    else if ($endsWith != '*') {
                        $directory = $directory . "/*";
                    }

                    $command = 'rm -r ' . $directory;

                    $this->info("Running command: '".$command."'");

                    exec($command, $shellResponse, $status);

                    if ($status != 0) {
                        throw new \Exception("Command '$command' could not be run!");
                        return;
                    }else {
                        $this->info("Directory " . $directory . ' cleaned.');
                    }
                }
            }
            $this->info("Directories cleaned");
        }
    }

}