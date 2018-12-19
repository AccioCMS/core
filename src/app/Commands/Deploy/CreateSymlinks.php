<?php

namespace Accio\App\Commands\Deploy;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class CreateSymlinks extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deploy:create_symlinks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create symlinks on deploy';

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
        $this->doSymLinks();
    }

    /**
     * Create symlinks
     *
     * @return $this
     */
    private function doSymLinks()
    {
        if(config('deploy.symlinks')) {

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