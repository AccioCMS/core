<?php

namespace Accio\App\Commands;

use Illuminate\Console\Command;

class DeployRelease extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deploy:release';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform all deployment tasks';

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
        $this->call('deploy:clean');
        $this->call('deploy:permissions');
        $this->call('deploy:uploads');
        $this->call('deploy:env');
        $this->call('deploy:db');
    }

}
