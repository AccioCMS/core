<?php

namespace Accio\App\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

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

                $command  = 'cp -r '.storage_path().' '.config('deploy.shared.to').'/';

                $this->process->setCommandLine($command);
                $this->process->setTimeout(null);
                $this->process->run();

                if (!$this->process->isSuccessful()) {
                    $this->error("Storage could not be cloned!");
                    return false;
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
     * @return $this
     * @throws \Exception
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
                $command = 'cp -R -u -p '.$from.' '.$to; // copy only when the SOURCE file is newer than the destination file or when the destination file is missing

                $this->info("Running command: '".$command."'");

                $this->process->setCommandLine($command);
                $this->process->setTimeout(null);
                $this->process->run();

                if (!$this->process->isSuccessful()) {
                    $this->error("Local upload files could not be copied to destination directory");
                    return false;
                }

                $this->info("Local uploads copied!");
            }
        }

        return $this;
    }

    /**
     * Create symlinks
     *
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