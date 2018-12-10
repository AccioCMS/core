<?php

namespace Accio\App\Commands\Deploy;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class EnvFile extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deploy:env --env=production';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deploy production env file';

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

    public function handle()
    {
        $this->comment("\nRenaming env file");

        // detect .env file name based on environment
        if($this->option('env') === 'test') {
            $nevFileName = '.env.test';
        }else{
            $nevFileName = '.env.production';
        }

        // Ensure .env file exists
        if(!file_exists(base_path($nevFileName))) {
            throw new \Exception(".env file could not be found on '".$this->option('env')."' environment");
        }else{

            // Delete default .env if it exists
            if(file_exists(base_path('.env'))) {
                unlink(base_path('.env'));
            }

            // Rename production env
            $command = 'mv '.base_path($nevFileName).' '.base_path('.env').'';

            $this->process->setCommandLine($command);
            $this->process->setTimeout(null);
            $this->process->run();

            if (!$this->process->isSuccessful()) {
                $this->error(".env file could not be renamed to .env on '".$this->option('env')."' environment");
                return false;
            }

            $this->info(".env file renamed!");
        }
    }

}
