<?php

namespace Accio\App\Commands;

use Illuminate\Console\Command;

class DeployUploads extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deploy:uploads';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deploy local uploads to production';

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

      //$this->doCloneStorage();
        //$this->doSymLinks();
        $this->doCopyUplaods();
    }

    /**
     * Clone storage
     *
     * @return $this|void
     */
    private function doCloneStorage(){
        // Clone storage path to shared storage direcetory
        $copyUploads = true;
        if(config('deploy.shared.from') && config('deploy.shared.to')){
            if(!file_exists(config('deploy.shared.to'))){

                $this->comment("\nCloning storage to shared directory");
                exec('cp -r '.storage_path().' '.config('deploy.shared.to').'/', $shellResponse, $status);

                if ($status != 0) {
                    throw new \Exception("Storage could not be cloned!");
                    return;
                }

                $this->info("Storage cloned");
                $copyUploads = false;
            }
        }
        return $this;
    }

    /**
     * Copy Uploads
     *
     * @return $this;
     */
    private function doCopyUplaods(){
        // Copy local uploads to shared storage directory
        if(config('deploy.uploads.from') && config('deploy.uploads.to')){
            $from = config('deploy.uploads.from');
            $fromNoAsterix = str_replace('/*', '', $from);
            $to = config('deploy.uploads.to');
            $toNoAsterix = str_replace('/*', '', $to);

            // both directories exist
            if(file_exists($fromNoAsterix) && file_exists($toNoAsterix)){
                $this->comment("\nCopying local uploads");
                $command = 'rsync -avzh '.$from.' '.$to;

                $this->info("Running command: '".$command."'");

                exec($command, $shellResponse, $status);

                if ($status != 0) {
                    throw new \Exception("Local upload files could not be copied to destination directory");
                    return;
                }

                $this->info("Local uploads copied!");
            }
        }

        return $this;
    }

    /**
     * Create symlinks
     * @return $this
     */
    private function doSymLinks(){
        if(config('deploy.symlinks')){

            $this->comment("\n\nCreating symlinks");
            foreach(config('deploy.symlinks') as $target => $link){
                if (file_exists($link)) {
                    $this->comment('The '.$link.' directory already exists.');
                }else{
                    $this->laravel->make('files')->link(
                        $target, $link
                    );
                }
            }

            $this->info("Symlinks created");
        }

        return $this;
    }

}