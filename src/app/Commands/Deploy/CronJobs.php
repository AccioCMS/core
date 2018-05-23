<?php

namespace Accio\App\Commands\Deploy;

use Accio\App\Services\Script;
use Accio\App\Services\ScriptParser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class Cronjobs extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deploy:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean caches on deploy';

    /**
     * @var ScriptParser 
     */
    protected $scriptParser;
    
    /**
     *
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Script $scriptParser)
    {
        parent::__construct();
        $this->scriptParser = $scriptParser;
    }

    /**
     * Execute the console command.
     *
     * @throws \Exception
     */
    public function handle(){
        if(config('deploy.cron')){
            $this->comment("\nCreating cron jobs");
            foreach(config('deploy.cron') as $cronCommand){
                $output = $this->scriptParser->parseFile('createCronJobs', [
                  'command' => $cronCommand,
                  'base_path' => base_path()
                ])->run($this);
                
                if(!$output){
                    return false;
                }
            }
        }
    }

}
