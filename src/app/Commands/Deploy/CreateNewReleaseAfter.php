<?php

namespace Accio\App\Commands\Deploy;

use Accio\App\Services\Script;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class CreateNewReleaseAfter extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deploy:create_new_release.after';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create New Release - After commands';

    /**
     * @var ScriptParser
     */
    protected $scriptParser;

    /**
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
     * @return mixed
     */
    public function handle()
    {
        $commands = config('deploy.commands.create_new_release.after');
        foreach($commands as $command){
            $output = $this->scriptParser->parseString($command, [
              'base_path' => base_path()
            ])->run($this);

            if(!$output){
                return false;
            }
        }
    }
}