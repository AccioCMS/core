<?php

namespace Accio\App\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class DeployEnv extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deploy:env';

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
        $this->comment("\nRenaming production env file");

        // Ensure .env.production file exists
        if(!file_exists(base_path('.env.production'))){
            throw new \Exception("Production Env file could not be found");
        }else{

            // Delete .env if it exists
            if(file_exists(base_path('.env'))){
                unlink(base_path('.env'));
            }

            // Rename production env
            $command = 'mv '.base_path('.env.production').' '.base_path('.env').'';

            $this->process->setCommandLine($command);
            $this->process->setTimeout(null);
            $this->process->run();

            if (!$this->process->isSuccessful()) {
                $this->error("Production Env file could not be renamed to .env");
                return false;
            }

            $this->info("Production env file renamed!");
        }
    }

}
