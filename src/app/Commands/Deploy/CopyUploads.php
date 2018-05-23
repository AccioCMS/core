<?php

namespace Accio\App\Commands\Deploy;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class CopyUploads extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deploy:copy_uploads';

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
        $this->doCopyUplaods();
    }

    /**
     * Copy Uploads
     *
     * @return $this
     * @throws \Exception
     */
    private function doCopyUplaods(){
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

}